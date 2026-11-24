@php
    /**
     * The One Tap prompt. Emits no markup of its own: the provider's script
     * draws the prompt, and the credential is sent on as an ordinary form POST.
     *
     * @var array|null $oneTap from OneTap::viewData(); null renders nothing
     */
    $oneTap = $oneTap ?? null;
@endphp

@if ($oneTap)
    <script>
        (function () {
            var options = @json($oneTap);

            function submit(response) {
                if (!response || !response.credential) { return; }

                var form   = document.createElement('form');
                var fields = { _token: options.token, credential: response.credential };

                if (options.returnPath) { fields[options.returnName] = options.returnPath; }

                form.method = 'post';
                form.action = options.action;
                form.hidden = true;

                Object.keys(fields).forEach(function (name) {
                    var input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = name;
                    input.value = fields[name];
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }

            function start() {
                var api = window.google && google.accounts && google.accounts.id;
                if (!api) { return; }

                if (options.signedOut) { api.disableAutoSelect(); }

                api.initialize({
                    client_id: options.clientId,
                    nonce: options.nonce,
                    auto_select: options.autoSelect,
                    itp_support: true,
                    callback: submit
                });
                api.prompt();
            }

            if (window.google && google.accounts && google.accounts.id) { start(); return; }

            var previous = window.onGoogleLibraryLoad;

            window.onGoogleLibraryLoad = function () {
                if (typeof previous === 'function') { previous(); }
                start();
            };

            if (!document.querySelector('script[src^="' + options.script + '"]')) {
                var script = document.createElement('script');
                script.src   = options.script;
                script.async = true;
                document.head.appendChild(script);
            }
        })();
    </script>
@endif
