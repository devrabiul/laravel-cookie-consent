<?php

use Devrabiul\CookieConsent\Commands\InstallSkillsCommand;
use Illuminate\Support\Facades\File;

function skillTargetFile(string $agent): string
{
    return base_path(InstallSkillsCommand::TARGETS[$agent]['path'] . '/SKILL.md');
}

beforeEach(function () {
    File::deleteDirectory(base_path('.claude'));
    File::deleteDirectory(base_path('.agents'));
});

afterEach(function () {
    File::deleteDirectory(base_path('.claude'));
    File::deleteDirectory(base_path('.agents'));
});

it('ships a skill with valid frontmatter in the Laravel Boost location', function () {
    $contents = File::get(InstallSkillsCommand::sourcePath());

    expect(preg_match('/\A---\n(.*?)\n---\n/s', $contents, $matches))->toBe(1);

    preg_match('/^name: (.+)$/m', $matches[1], $name);
    preg_match('/^description: (.+)$/m', $matches[1], $description);

    // Agent Skills spec: the name matches the folder, lowercase letters, digits and hyphens, max 64 chars;
    // the description is required and at most 1024 characters.
    expect($name[1] ?? null)->toBe('laravel-cookie-consent')
        ->and(basename(dirname(InstallSkillsCommand::sourcePath())))->toBe('laravel-cookie-consent')
        ->and(strlen($description[1] ?? ''))->toBeGreaterThan(0)->toBeLessThanOrEqual(1024);
});

it('installs the skill for Claude Code and Antigravity by default', function () {
    $this->artisan('cookie-consent:skills')->assertExitCode(0);

    expect(File::get(skillTargetFile('claude')))->toBe(File::get(InstallSkillsCommand::sourcePath()))
        ->and(File::get(skillTargetFile('antigravity')))->toBe(File::get(InstallSkillsCommand::sourcePath()));
});

it('installs the skill for a single agent', function () {
    $this->artisan('cookie-consent:skills', ['--claude' => true])->assertExitCode(0);

    expect(File::exists(skillTargetFile('claude')))->toBeTrue()
        ->and(File::exists(skillTargetFile('antigravity')))->toBeFalse();
});

it('does not overwrite an existing skill without --force', function () {
    File::ensureDirectoryExists(dirname(skillTargetFile('claude')));
    File::put(skillTargetFile('claude'), 'customised');

    $this->artisan('cookie-consent:skills', ['--claude' => true])->assertExitCode(0);
    expect(File::get(skillTargetFile('claude')))->toBe('customised');

    $this->artisan('cookie-consent:skills', ['--claude' => true, '--force' => true])->assertExitCode(0);
    expect(File::get(skillTargetFile('claude')))->toBe(File::get(InstallSkillsCommand::sourcePath()));
});

it('does not publish the skill with vendor:publish', function () {
    $paths = array_values(\Illuminate\Support\ServiceProvider::pathsToPublish(
        \Devrabiul\CookieConsent\CookieConsentServiceProvider::class
    ));

    expect(implode("\n", $paths))->not->toContain('.claude')->not->toContain('.agents');
});
