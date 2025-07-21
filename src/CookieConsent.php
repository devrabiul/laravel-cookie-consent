<?php

namespace Devrabiul\CookieConsent;

use Illuminate\Contracts\View\View;
use Illuminate\Session\SessionManager as Session;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

/**
 * Class CookieConsent
 *
 * Handles the display and management of cookie consent UI components in a Laravel application.
 */
class CookieConsent
{
    /**
     * The session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * The Config handler instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The JavaScript type for script tags.
     *
     * @var string
     */
    protected string $jsType = 'text/javascript';

    /**
     * CookieConsent constructor.
     *
     * @param Session $session The session manager instance.
     * @param Config $config The configuration repository instance.
     */
    function __construct(Session $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * Generate the HTML for the required stylesheet.
     *
     * @return string The HTML link tag for the stylesheet.
     */
    public function styles(): string
    {
        $stylePath = 'vendor/devrabiul/laravel-cookie-consent/css/style.css';
        if (File::exists(public_path($stylePath))) {
            return '<link rel="stylesheet" href="' . $this->getDynamicAsset($stylePath) . '">';
        }
        return '<link rel="stylesheet" href="' . url('vendor/devrabiul/laravel-cookie-consent/assets/css/style.css') . '">';
    }

    /**
     * Render the cookie consent view with the given configuration.
     *
     * @param array $cookieConfig Optional cookie configuration overrides.
     * @return View The cookie consent view.
     */
    public function content(array $cookieConfig = [])
    {
        return view('laravel-cookie-consent::cookie-consent', compact('cookieConfig'));
    }

    /**
     * Generate the HTML for the required JavaScript with optional configuration overrides.
     *
     * @param array $options Optional configuration overrides.
     * @return View The cookie consent script view.
     */
    public function scripts(array $options = []): mixed
    {
        $config = (array)$this->config->get('laravel-cookie-consent');
        $config = array_merge($config, $options);
        if (isset($config['enabled']) && ($config['enabled'] === false || $config['enabled'] === 'false')) {
            return '';
        }
        return self::content(cookieConfig: $config);
    }

    /**
     * Generate the HTML for the required scripts.
     *
     * @return string The HTML link tag for the scripts.
     */
    public function scriptsPath(): string
    {
        $script = $this->scriptTag('vendor/devrabiul/laravel-cookie-consent/assets/js/script.js');
        $defaultJsPath = 'vendor/devrabiul/laravel-cookie-consent/js/script.js';
        if (File::exists(public_path($defaultJsPath))) {
            $script = $this->scriptTag($defaultJsPath);
        }

        $script .= '<script src="' . route('laravel-cookie-consent.script-utils') . '"></script>';
        return $script;
    }

    /**
     * Generate a script tag with the given source path.
     *
     * @param string $src
     * @return string
     */
    private function scriptTag(string $src): string
    {
        return '<script src="' . $this->getDynamicAsset($src) . '"></script>';
    }

    /**
     * Resolve the dynamic asset path depending on processing directory and environment.
     *
     * @param string $path
     * @return string
     */
    private function getDynamicAsset(string $path): string
    {
        if (self::getProcessingDirectoryConfig() == 'public') {
            $position = strpos($path, 'public/');
            $result = $path;
            if ($position === 0) {
                $result = preg_replace('/public/', '', $path, 1);
            }
        } else {
            $result = in_array(request()->ip(), ['127.0.0.1']) ? $path : 'public/' . $path;
        }

        return asset($result);
    }


    private function getProcessingDirectoryConfig(): string
    {
        $scriptPath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        $basePath   = realpath(base_path());
        $publicPath = realpath(public_path());

        if ($scriptPath === $publicPath) {
            $systemProcessingDirectory = 'public';
        } elseif ($scriptPath === $basePath) {
            $systemProcessingDirectory = 'root';
        } else {
            $systemProcessingDirectory = 'unknown';
        }

        return $systemProcessingDirectory;
    }

    public static function getRemoveInvalidCharacters($str): array|string
    {
        return str_ireplace(['"', ';', '<', '>'], ' ', preg_replace('/\s\s+/', ' ', $str));
    }

    /**
     * Translates a given key using the `messages.php` language file in the current locale.
     *
     * - If the key does not exist in the file, it creates the file and inserts the key with a default value.
     * - It ensures the required language directory and file exist.
     * - Falls back to Laravel translation helper if the key is not found after insertion.
     *
     * @param string $key The translation key, e.g. 'messages.privacy_policy'.
     * @param null $default The default value to use if the key doesn't exist.
     */
    public static function translate(string $key, $default = null)
    {
        $locale = app()->getLocale();
        $langDir = resource_path("lang/{$locale}");
        $filePath = "{$langDir}/messages.php";

        // Ensure the directory exists
        if (!File::exists($langDir)) {
            File::makeDirectory($langDir, 0755, true);
        }

        // If the file doesn't exist, create it with an empty array
        if (!File::exists($filePath)) {
            File::put($filePath, "<?php\n\nreturn [\n];");
        }

        try {
            // Load existing translations
            $translations = File::getRequire($filePath);

            if (!empty($key)) {
                $processedKey = str_replace('_', ' ', CookieConsent::getRemoveInvalidCharacters(str_replace("\'", "'", $key)));
                $actualKey = CookieConsent::getRemoveInvalidCharacters($key);

                // Add key if it doesn't exist
                if (!array_key_exists($actualKey, $translations)) {
                    $translations[$actualKey] = $default ?? $processedKey;

                    $content = "<?php\n\nreturn [\n";
                    foreach ($translations as $k => $v) {
                        $content .= "    '{$k}' => '{$v}',\n";
                    }
                    $content .= "];\n";

                    File::put($filePath, $content);
                } elseif (array_key_exists($actualKey, $translations)) {
                    return $translations[$actualKey] ?? $default;
                } else {
                    return __('messages.' . $actualKey);
                }
            }
        } catch (\Exception $exception) {

        }
        return $key;
    }
}
