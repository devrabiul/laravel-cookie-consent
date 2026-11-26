<?php

namespace Devrabiul\CookieConsent\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Copy the package's AI agent skill into the application, so coding agents such as
 * Claude Code and Google Antigravity know how to use the package.
 *
 * Projects using Laravel Boost do not need this command: Boost picks the skill up
 * from resources/boost/skills automatically.
 */
class InstallSkillsCommand extends Command
{
    /**
     * Skill folders, relative to the application's base path, per agent.
     */
    public const TARGETS = [
        'claude' => ['label' => 'Claude Code', 'path' => '.claude/skills/laravel-cookie-consent'],
        'antigravity' => ['label' => 'Google Antigravity', 'path' => '.agents/skills/laravel-cookie-consent'],
    ];

    protected $signature = 'cookie-consent:skills
                            {--claude : Install the skill for Claude Code (.claude/skills)}
                            {--antigravity : Install the skill for Google Antigravity (.agents/skills)}
                            {--force : Overwrite an existing skill, e.g. after updating the package}';

    protected $description = 'Install the Laravel Cookie Consent AI agent skill for Claude Code and Google Antigravity';

    public function handle(Filesystem $files): int
    {
        $source = self::sourcePath();

        $agents = array_keys(array_filter([
            'claude' => $this->option('claude'),
            'antigravity' => $this->option('antigravity'),
        ]));
        if ($agents === []) {
            $agents = array_keys(self::TARGETS);
        }

        foreach ($agents as $agent) {
            $target = self::TARGETS[$agent];
            $file = base_path($target['path'] . '/SKILL.md');

            if ($files->exists($file) && !$this->option('force')) {
                $this->warn("{$target['label']}: {$target['path']}/SKILL.md already exists. Use --force to overwrite it.");
                continue;
            }

            $files->ensureDirectoryExists(dirname($file));
            $files->copy($source, $file);

            $this->info("{$target['label']}: skill installed in {$target['path']}/SKILL.md");
        }

        return self::SUCCESS;
    }

    /**
     * Path of the skill shipped with the package.
     */
    public static function sourcePath(): string
    {
        return dirname(__DIR__, 2) . '/resources/boost/skills/laravel-cookie-consent/SKILL.md';
    }
}
