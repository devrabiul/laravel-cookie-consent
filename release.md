## v2.3 – AI agent skill for Claude Code, Google Antigravity and Laravel Boost

This release adds an [Agent Skill](https://agentskills.io) that teaches AI coding agents how to use Laravel Cookie Consent correctly. When you ask an agent for something like "add Google Analytics, but only after cookie consent", it will:

- place `CookieConsent::styles()` and `CookieConsent::scripts()` in the right spots
- gate tracking scripts with Google Consent Mode v2, `js_action` or `@cookieConsent`
- enable the cookie categories it needs and check consent with `CookieConsent::hasConsent()`
- avoid common mistakes, such as putting the Google tag above the consent defaults, bumping `consent_version` without a reason, or gating on a disabled category

The agent loads the skill only when a task involves cookie consent.

### Install the skill

**With Laravel Boost**

```bash
php artisan boost:install
```

Select `devrabiul/laravel-cookie-consent` when asked *"Which third-party AI guidelines/skills would you like to install?"*. After that, `php artisan boost:update` keeps the skill up to date.

**Without Laravel Boost**

```bash
php artisan cookie-consent:skills                 # Claude Code and Google Antigravity
php artisan cookie-consent:skills --claude        # .claude/skills/laravel-cookie-consent
php artisan cookie-consent:skills --antigravity   # .agents/skills/laravel-cookie-consent
php artisan cookie-consent:skills --force         # overwrite after updating the package
```

Commit the created folder so your whole team gets the skill.

### Upgrading

```bash
composer update devrabiul/laravel-cookie-consent
```

There are no breaking changes, and there's nothing to change in your config or views. `vendor:publish` still publishes only the config file. The skill is never added to your project unless you run one of the commands above.

**Full changelog:** https://github.com/devrabiul/laravel-cookie-consent/compare/v2.2...v2.3
