<?php

namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Contracts\HttpClientInterface;

class Test implements HttpClientInterface
{
    use HttpClientTrait;

    private static array $defaultOptions = self::OPTIONS_DEFAULTS + [
        'auth_ntlm' => null, // array|string - an array containing the username as first value, and optionally the
        'extra' => [
            'curl' => [],
        ],
    ];
    private static array $emptyDefaults = self::OPTIONS_DEFAULTS + ['auth_ntlm' => null];

    public static function testing()
    {
        $url = "https://www.notion.so/35dedf7c4e4b4552becf52671ad53d85";
        var_dump(self::prepareRequest("GET", $url, self::$defaultOptions, self::$defaultOptions));
    }

    public  static function getDefinedKPHP($filename = __DIR__.'/../../_functions.txt') {
        $constants = array();
        $lines = file($filename);
        foreach ($lines as $line) {
            if (preg_match('/^define\((\'|\")(\w+)(\'|\")/', $line, $matches)) {
                $constants[] = $matches[2];
            }
        }
        return $constants;
    }

    public static function phpPredefinedToFile($filename = __DIR__."/PredefinedConstants.php")
    {
        $constants = get_defined_constants(true);
        $kConstants = self::getDefinedKPHP();
        $content = "<?php\n";
        foreach (array_merge($constants['json'], $constants['curl']) as $name => $value) {
            if (in_array($name, $kConstants)) continue;
            $content .= "define('$name', $value);\n";
        }
        file_put_contents($filename, $content);
    }
}