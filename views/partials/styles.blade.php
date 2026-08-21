@php
    use EvolutionCMS\aSocialAuth\Support\Renderer;

    /**
     * The package's own stylesheet — a starting point, not a requirement.
     *
     * Two escape hatches, because a widget that cannot be restyled is a widget
     * nobody will use twice:
     *
     *   - every colour, radius and gap is a CSS custom property on
     *     `.asocialauth`, so a theme can retint the whole thing with a handful
     *     of declarations and no specificity fight;
     *   - `ui.inline_styles => false` drops this block entirely, leaving clean
     *     classed markup for a design system to style from scratch.
     *
     * Emitted once per request even when several widgets are on the page.
     */
    $emit = Renderer::shouldEmitStyles();
@endphp

@if ($emit)
<style>
    .asocialauth {
        --asa-border: #dee2e6;
        --asa-bg: #fff;
        --asa-fg: #3d3d3d;
        --asa-muted: #818a91;
        --asa-accent: #2b6cb0;
        --asa-radius: 20px;
        --asa-radius-sm: 8px;
        --asa-gap: .5rem;
        --asa-error-bg: #fdecea;
        --asa-error-fg: #8a1c12;
        --asa-error-border: #f5c6c2;
        --asa-ok-bg: #eaf7ee;
        --asa-ok-fg: #14632a;
        --asa-ok-border: #bfe3ca;

        color: var(--asa-fg);
    }

    /* ---- shared ---- */
    .asocialauth__heading { font-size: 1rem; font-weight: 600; margin: 0 0 .5rem; }
    .asocialauth__intro { color: var(--asa-muted); font-size: .82rem; margin: 0 0 .75rem; }
    .asocialauth__divider {
        display: flex; align-items: center; gap: .75rem;
        margin: 1rem 0 .75rem; color: var(--asa-muted); font-size: .8rem;
    }
    .asocialauth__divider::before, .asocialauth__divider::after {
        content: ''; flex: 1; border-top: 1px solid var(--asa-border);
    }
    .asocialauth__msg {
        margin-bottom: .75rem; padding: .5rem .75rem;
        border-radius: var(--asa-radius-sm); font-size: .85rem;
    }
    .asocialauth__msg--error {
        background: var(--asa-error-bg); color: var(--asa-error-fg);
        border: 1px solid var(--asa-error-border);
    }
    .asocialauth__msg--ok {
        background: var(--asa-ok-bg); color: var(--asa-ok-fg);
        border: 1px solid var(--asa-ok-border);
    }

    /* ---- provider buttons ---- */
    .asocialauth__buttons { display: flex; flex-direction: column; gap: var(--asa-gap); }
    .asocialauth__btn {
        display: flex; align-items: center; justify-content: center; gap: var(--asa-gap);
        width: 100%; min-height: 44px; padding: .5rem 1rem;
        border: 1px solid var(--asa-border); border-radius: var(--asa-radius);
        background: var(--asa-bg); color: var(--asa-fg);
        font-size: .9rem; font-weight: 500; text-decoration: none; cursor: pointer;
        transition: background .15s, box-shadow .15s;
    }
    .asocialauth__btn:hover {
        background: #f8f9fa; box-shadow: 0 1px 4px rgba(0,0,0,.12);
        color: var(--asa-fg); text-decoration: none;
    }
    .asocialauth__btn svg { flex-shrink: 0; }

    /* ---- credential forms ---- */
    .asocialauth__form { display: flex; flex-direction: column; gap: .6rem; }
    .asocialauth__field { display: flex; flex-direction: column; gap: .25rem; }
    .asocialauth__label { font-size: .78rem; color: var(--asa-muted); }
    .asocialauth__input {
        width: 100%; padding: .5rem .75rem; font-size: .9rem;
        border: 1px solid var(--asa-border); border-radius: var(--asa-radius-sm);
        background: var(--asa-bg); color: var(--asa-fg);
    }
    .asocialauth__input:focus { outline: 2px solid var(--asa-accent); outline-offset: 1px; }
    .asocialauth__check { display: flex; align-items: center; gap: .4rem; font-size: .82rem; }
    .asocialauth__submit {
        min-height: 44px; padding: .5rem 1rem; cursor: pointer;
        border: 1px solid var(--asa-accent); border-radius: var(--asa-radius);
        background: var(--asa-accent); color: #fff; font-size: .9rem; font-weight: 600;
    }
    .asocialauth__submit:hover { filter: brightness(1.08); }
    .asocialauth__links {
        display: flex; flex-wrap: wrap; gap: .75rem;
        font-size: .8rem; margin-top: .25rem;
    }

    /* ---- identities widget ---- */
    .asocialauth__list { list-style: none; margin: 0 0 1rem; padding: 0; display: flex; flex-direction: column; gap: var(--asa-gap); }
    .asocialauth__item {
        display: flex; align-items: center; gap: .75rem;
        padding: .6rem .75rem; border: 1px solid var(--asa-border); border-radius: 10px;
    }
    .asocialauth__item-body { flex: 1; min-width: 0; }
    .asocialauth__item-name { font-weight: 600; font-size: .9rem; }
    .asocialauth__item-meta { color: var(--asa-muted); font-size: .78rem; word-break: break-word; }
    .asocialauth__item form { margin: 0; }
    .asocialauth__unlink {
        border: 1px solid var(--asa-border); border-radius: 16px; background: transparent;
        color: var(--asa-muted); font-size: .78rem; padding: .3rem .75rem; cursor: pointer;
    }
    .asocialauth__unlink:hover {
        color: var(--asa-error-fg); border-color: var(--asa-error-border); background: var(--asa-error-bg);
    }
    .asocialauth__unlink[disabled] { opacity: .5; cursor: not-allowed; }
    .asocialauth__empty { color: var(--asa-muted); font-size: .85rem; margin-bottom: 1rem; }

    /* ---- modal ---- */
    .asocialauth__trigger {
        min-height: 44px; padding: .5rem 1.25rem; cursor: pointer;
        border: 1px solid var(--asa-accent); border-radius: var(--asa-radius);
        background: var(--asa-accent); color: #fff; font-size: .9rem; font-weight: 600;
    }
    .asocialauth__panel--modal {
        width: min(24rem, calc(100vw - 2rem));
        padding: 0; border: 1px solid var(--asa-border); border-radius: 12px;
        background: var(--asa-bg); color: var(--asa-fg);
    }
    .asocialauth__panel--modal::backdrop { background: rgba(0,0,0,.45); }
    .asocialauth__inner { padding: 1.25rem; position: relative; }
    .asocialauth__close {
        position: absolute; top: .5rem; right: .6rem;
        border: 0; background: transparent; cursor: pointer;
        color: var(--asa-muted); font-size: 1.4rem; line-height: 1;
    }
    /*
       No-JS fallback: an un-enhanced <dialog> would be display:none and the
       trigger would do nothing, so show it inline instead. `is-enhanced` is
       added by the widget's script, which only runs where showModal() exists.
    */
    .asocialauth__panel--modal:not(.is-enhanced) { display: block; position: static; }
    .asocialauth__panel--modal:not(.is-enhanced) ~ .asocialauth__trigger,
    .asocialauth--modal .asocialauth__panel--modal:not(.is-enhanced) .asocialauth__close { display: none; }
    .asocialauth--modal .asocialauth__panel--modal.is-enhanced:not([open]) { display: none; }

    @media (prefers-color-scheme: dark) {
        .asocialauth {
            --asa-border: #444;
            --asa-bg: #2c2c2c;
            --asa-fg: #e0e0e0;
            --asa-muted: #888;
            --asa-accent: #4c8fd6;
            --asa-error-bg: #3a1f1d;
            --asa-error-fg: #f3b5b0;
            --asa-error-border: #5c2b27;
            --asa-ok-bg: #1d3524;
            --asa-ok-fg: #a9dcb8;
            --asa-ok-border: #2b4c34;
        }
        .asocialauth__btn:hover { background: #3a3a3a; color: #fff; }
    }
</style>
@endif
