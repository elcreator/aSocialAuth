<?php

/**
 * Test bootstrap.
 *
 * The package's unit tests deliberately do not boot Evolution CMS. What is under
 * test here is the package's own logic — URL assembly, the provider catalogue,
 * flow state, profile reading, the shape of the login payload — and all of it is
 * reachable without a database or a running CMS. The few framework functions
 * those classes touch are stubbed below, so a failing test points at this
 * package rather than at a fixture.
 *
 * Illuminate and the Evolution CMS classes themselves come from the nested
 * autoloader of the evolution-cms/evolution dependency.
 */

/**
 * A stand-in for the framework's config repository.
 */
final class TestConfig
{
    public static array $items = [];

    public static function reset(): void
    {
        self::$items = [
            'cms' => ['settings' => ['aSocialAuth' => require __DIR__ . '/../config/aSocialAuth.php']],
            'app' => ['middleware' => ['global' => ['GlobalMiddleware']]],
        ];
    }

    public static function get(string $key, $default = null)
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $target    = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }
}

/**
 * Records what the package asked the core to do, and lets a test make the core
 * answer the way a configured login pipe would.
 */
final class UserManager
{
    /** @var array<int, array> every payload passed to loginById() */
    public static array $logins = [];

    /** @var \Throwable|null thrown by the next loginById() call */
    public static ?Throwable $throw = null;

    public static function reset(): void
    {
        self::$logins = [];
        self::$throw  = null;
    }

    public static function loginById(array $userData)
    {
        self::$logins[] = $userData;

        if (self::$throw !== null) {
            $throw       = self::$throw;
            self::$throw = null;

            throw $throw;
        }

        return (object) ['id' => $userData['id'] ?? null];
    }

    public static function lastLogin(): ?array
    {
        return self::$logins === [] ? null : self::$logins[count(self::$logins) - 1];
    }

    /**
     * Mirrors the core's UserRegistration: a row in `users` and one in
     * `user_attributes`, which is the split the package must respect.
     */
    public static function create(array $userData)
    {
        if (\EvolutionCMS\Models\User::query()->where('username', $userData['username'])->exists()) {
            throw new \EvolutionCMS\Exceptions\ServiceActionException('username taken');
        }

        if (\EvolutionCMS\Models\UserAttribute::query()->where('email', $userData['email'])->exists()) {
            throw new \EvolutionCMS\Exceptions\ServiceActionException('email taken');
        }

        $userId = \EvolutionCMS\Models\User::query()->insertGetId([
            'username' => $userData['username'],
            'password' => 'hashed:' . $userData['password'],
        ]);

        \EvolutionCMS\Models\UserAttribute::query()->insert([
            'internalKey' => $userId,
            'email'       => $userData['email'],
            'fullname'    => $userData['fullname'] ?? '',
            'verified'    => $userData['verified'] ?? 0,
            'blocked'     => $userData['blocked'] ?? 0,
            // role is NOT settable here, exactly as in the core.
            'role'        => 0,
        ]);

        return \EvolutionCMS\Models\User::query()->find($userId);
    }

    /** Mirrors UserSetRole: the one door a role change goes through. */
    public static function setRole(array $userData)
    {
        \EvolutionCMS\Models\UserAttribute::query()
            ->where('internalKey', $userData['id'])
            ->update(['role' => (int) $userData['role']]);

        return \EvolutionCMS\Models\User::query()->find($userData['id']);
    }

    public static function setGroups(array $userData)
    {
        return \EvolutionCMS\Models\User::query()->find($userData['id']);
    }

    /**
     * Mirrors the core's UserGetVerifiedKey: stamp a fresh key on the user row.
     */
    public static function getVerifiedKey(array $userData)
    {
        $user = \EvolutionCMS\Models\User::query()->find($userData['id']);

        $user->verified_key = md5(uniqid('', true));
        $user->save();

        return $user;
    }

    /**
     * Mirrors the core's UserVerified: spend the key and flip the flag.
     */
    public static function verified(array $userData)
    {
        $user = \EvolutionCMS\Models\User::query()
            ->where('username', $userData['username'])
            ->where('verified_key', $userData['verified_key'])
            ->first();

        if ($user === null) {
            throw new \EvolutionCMS\Exceptions\ServiceActionException('user does not exist');
        }

        $user->verified_key = null;
        $user->save();

        \EvolutionCMS\Models\UserAttribute::query()
            ->where('internalKey', $user->getKey())
            ->update(['verified' => 1]);

        return $user;
    }
}

/**
 * Minimal Evolution CMS surface: only what the package actually calls.
 */
final class TestEvoApp
{
    public array $events = [];
    public array $log    = [];

    /** Messages the package tried to send, and whether sending should succeed. */
    public array $mail       = [];
    public bool  $mailWorks  = true;

    public function sendmail($params = [], $msg = '', $files = [])
    {
        $this->mail[] = $params;

        return $this->mailWorks;
    }

    public function lastMail(): ?array
    {
        return $this->mail === [] ? null : $this->mail[count($this->mail) - 1];
    }

    public function getConfig(string $key, $default = null)
    {
        return match ($key) {
            'site_url' => 'https://example.test/',
            'base_url' => '/',
            default    => $default,
        };
    }

    public function logEvent($id, $type, $message, $source = '')
    {
        $this->log[] = compact('id', 'type', 'message', 'source');
    }

    public function invokeEvent($name, $params = [])
    {
        $this->events[] = compact('name', 'params');

        return true;
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        if ($key === null) {
            return TestConfig::$items;
        }

        return TestConfig::get($key, $default);
    }
}

if (!function_exists('evo')) {
    function evo(): TestEvoApp
    {
        static $app = null;

        return $app ??= new TestEvoApp();
    }
}

if (!function_exists('app')) {
    function app($abstract = null)
    {
        // No container in unit tests. Callers all guard with try/catch, and the
        // absence of a cache is a state the package must handle anyway.
        throw new RuntimeException('no container in unit tests');
    }
}

if (!function_exists('__')) {
    /**
     * Resolves against the package's real language files, so a test that asks
     * for a message also proves the key exists.
     */
    function __($key, array $replace = [])
    {
        if (!str_starts_with((string) $key, 'aSocialAuth::')) {
            return $key;
        }

        [$file, $entry] = array_pad(explode('.', substr($key, strlen('aSocialAuth::')), 2), 2, '');

        $path = __DIR__ . '/../lang/en/' . $file . '.php';

        if (!is_file($path)) {
            return $key;
        }

        $lines = require $path;

        if (!isset($lines[$entry])) {
            return $key;
        }

        $line = $lines[$entry];

        foreach ($replace as $search => $value) {
            $line = str_replace(':' . $search, (string) $value, $line);
        }

        return $line;
    }
}

if (!function_exists('now')) {
    function now()
    {
        return new DateTimeImmutable();
    }
}

/*
 * Only now pull in the Evolution CMS dependency's own autoloader, for Illuminate
 * and the EvolutionCMS\* classes. It registers helper functions of its own —
 * evo(), config() — behind function_exists() guards, so the stubs above have to
 * be declared first to win. Its evo() would otherwise try to boot a real CMS.
 */
$evoAutoload = __DIR__ . '/../vendor/evolution-cms/evolution/core/vendor/autoload.php';

if (is_file($evoAutoload)) {
    require_once $evoAutoload;
}

/**
 * An in-memory database with the package's own schema.
 *
 * The models are real Eloquent models, so testing them against a real connection
 * costs one SQLite file in memory and buys coverage of the things that actually
 * go wrong — unique constraints, the provider foreign key, a claim expiring.
 *
 * Only this package's tables exist here, plus the two Evolution CMS ones the
 * package reads. Booting the whole CMS schema would be a fixture to maintain
 * rather than a test.
 */
function bootTestDatabase(): void
{
    static $capsule = null;

    if ($capsule === null) {
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    $schema = $capsule::schema();

    foreach (['social_email_verifications', 'social_accounts', 'social_providers', 'user_attributes', 'users'] as $table) {
        $schema->dropIfExists($table);
    }

    $schema->create('users', function ($table) {
        $table->increments('id');
        $table->string('username')->default('');
        $table->string('password')->default('');
        $table->string('cachepwd')->default('');
        $table->string('verified_key')->nullable();
    });

    $schema->create('user_attributes', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('internalKey')->default(0);
        $table->string('fullname')->default('');
        $table->unsignedInteger('role')->default(0);
        $table->string('email')->default('');
        $table->tinyInteger('verified')->default(0);
        $table->unsignedInteger('blocked')->default(0);
        $table->unsignedInteger('createdon')->default(0);
        $table->unsignedInteger('editedon')->default(0);
    });

    $schema->create('social_providers', function ($table) {
        $table->increments('id');
        $table->string('slug', 64)->unique();
        $table->string('adapter', 191);
        $table->string('label', 191)->default('');
        $table->boolean('enabled')->default(false);
        $table->integer('sort')->default(0);
        $table->string('icon', 64)->nullable();
        $table->timestamps();
    });

    $schema->create('social_accounts', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('provider_id');
        $table->string('provider_user_id', 255);
        $table->unsignedInteger('user_id');
        $table->string('email')->nullable();
        $table->boolean('email_verified')->default(false);
        $table->string('name')->nullable();
        $table->string('avatar', 2048)->nullable();
        $table->unsignedInteger('last_login_at')->nullable();
        $table->timestamps();
        $table->unique(['provider_id', 'provider_user_id']);
    });

    $schema->create('social_email_verifications', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('user_id')->unique();
        $table->string('email');
        $table->string('token', 64)->unique();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
}

/**
 * Insert a user the way UserManager::create() would: a row in each table.
 */
function makeUser(array $attributes = []): int
{
    $attributes += [
        'username' => 'user' . random_int(1000, 999999),
        'email'    => 'user@example.test',
        'verified' => 1,
        'role'     => 0,
    ];

    $userId = \EvolutionCMS\Models\User::query()->insertGetId([
        'username' => $attributes['username'],
        'password' => 'hash',
    ]);

    \EvolutionCMS\Models\UserAttribute::query()->insert([
        'internalKey' => $userId,
        'email'       => $attributes['email'],
        'fullname'    => $attributes['username'],
        'verified'    => $attributes['verified'],
        'role'        => $attributes['role'],
    ]);

    return (int) $userId;
}

/**
 * Insert a provider registry row and return the model.
 */
function insertProvider(string $slug, array $attributes = []): \EvolutionCMS\aSocialAuth\Models\RegisteredProvider
{
    $case = \EvolutionCMS\aSocialAuth\Enums\SocialProvider::fromSlug($slug);

    return \EvolutionCMS\aSocialAuth\Models\RegisteredProvider::query()->create(array_merge([
        'slug'    => $slug,
        'adapter' => $case?->hybridauthProvider() ?? ucfirst($slug),
        'label'   => $case?->label() ?? ucfirst($slug),
        'enabled' => true,
        'sort'    => 0,
    ], $attributes));
}

/**
 * Reset every piece of global state between tests.
 */
function resetTestState(): void
{
    TestConfig::reset();
    UserManager::reset();
    $_SESSION = [];

    \EvolutionCMS\aSocialAuth\Support\ProviderRegistry::flush();
    \EvolutionCMS\aSocialAuth\Support\Renderer::resetStyles();

    evo()->mail      = [];
    evo()->events    = [];
    evo()->mailWorks = true;

    bootTestDatabase();
}

/**
 * Build a HybridAuth profile the way a provider would return one.
 */
function makeProfile(array $attributes = []): \Hybridauth\User\Profile
{
    $profile = new \Hybridauth\User\Profile();

    foreach ($attributes as $key => $value) {
        $profile->$key = $value;
    }

    return $profile;
}

/**
 * A registry row without touching the database.
 */
function makeProvider(string $slug, array $attributes = []): \EvolutionCMS\aSocialAuth\Models\RegisteredProvider
{
    $case = \EvolutionCMS\aSocialAuth\Enums\SocialProvider::fromSlug($slug);

    $provider = new \EvolutionCMS\aSocialAuth\Models\RegisteredProvider();
    $provider->forceFill(array_merge([
        'id'      => crc32($slug),
        'slug'    => $slug,
        'adapter' => $case?->hybridauthProvider() ?? ucfirst($slug),
        'label'   => $case?->label() ?? ucfirst($slug),
        'enabled' => true,
        'sort'    => 0,
    ], $attributes));

    return $provider;
}

TestConfig::reset();

uses()->beforeEach(fn () => resetTestState())->in('Unit');
