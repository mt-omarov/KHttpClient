<?php
namespace Kaa\HttpClient\Components;

require_once __DIR__.'/PredefinedConstants.php';
trait HttpClientTrait
{
    public static int $CHUNK_SIZE = 16372;

    private static function prepareRequest(?string $method, ?string $url, array $options, array $defaultOptions = [], bool $allowExtraOptions = false): array
    {
        // если название метода указано, проверяется его корректность
        if (null !== $method) {
            // здесь проверяется, что метод содержит только буквы верхнего регистра
            if (\strlen($method) !== strspn($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')) {
                throw new \Exception(sprintf('Invalid HTTP method "%s", only uppercase letters are accepted.', $method));
            }
            if (!$method) {
                throw new \Exception('The HTTP method cannot be empty.');
            }
        }
        // тут происходит склеивание передаваемых опций с опциями по умолчанию, валидация и парсинг url-запроса и обработка исключений.
        $options = self::mergeDefaultOptions($options, $defaultOptions, $allowExtraOptions);

        if (isset($options['json'])) {
            if (isset($options['body']) && '' !== $options['body']) {
                throw new \Exception('Define either the "json" or the "body" option, setting both is not supported.');
            }
            $options['body'] = self::jsonEncode($options['json']);
            unset($options['json']);

            if (!isset($options['normalized_headers']['content-type'])) {
                $options['normalized_headers']['content-type'] = ['Content-Type: application/json'];
            }
        }

        if (!isset($options['normalized_headers']['accept'])) {
            $options['normalized_headers']['accept'] = ['Accept: */*'];
        }

//        if (isset($options['body'])) {
//            if (\is_array($options['body']) && (!isset($options['normalized_headers']['content-type'][0]) || !str_contains($options['normalized_headers']['content-type'][0], 'application/x-www-form-urlencoded'))) {
//                $options['normalized_headers']['content-type'] = ['Content-Type: application/x-www-form-urlencoded'];
//            }
//
//            $options['body'] = self::normalizeBody($options['body']);
//
//            if (\is_string($options['body'])
//                && (string) \strlen($options['body']) !== substr($h = $options['normalized_headers']['content-length'][0] ?? '', 16)
//                && ('' !== $h || '' !== $options['body'])
//            ) {
//                if ('chunked' === substr($options['normalized_headers']['transfer-encoding'][0] ?? '', \strlen('Transfer-Encoding: '))) {
//                    unset($options['normalized_headers']['transfer-encoding']);
//                    $options['body'] = self::dechunk($options['body']);
//                }
//
//                $options['normalized_headers']['content-length'] = [substr_replace($h ?: 'Content-Length: ', \strlen($options['body']), 16)];
//            }
//        }

        return $options;
    }

    private static function normalizeBody($body)
    {
        if (\is_array($body)) {
            array_walk_recursive($body, $caster = static function (&$v) use (&$caster) {
                if (\is_object($v)) {
                    if ($vars = get_object_vars($v)) {
                        array_walk_recursive($vars, $caster);
                        $v = $vars;
                    } elseif (method_exists($v, '__toString')) {
                        $v = (string) $v;
                    }
                }
            });

            return http_build_query($body, '', '&');
        }

        if (\is_string($body)) {
            return $body;
        }

//        $generatorToCallable = static fn (\Generator $body): \Closure => static function () use ($body) {
//            while ($body->valid()) {
//                $chunk = $body->current();
//                $body->next();
//
//                if ('' !== $chunk) {
//                    return $chunk;
//                }
//            }
//
//            return '';
//        };
//
//        if ($body instanceof \Generator) {
//            return $generatorToCallable($body);
//        }
//
//        if ($body instanceof \Traversable) {
//            return $generatorToCallable((static function ($body) { yield from $body; })($body));
//        }
//
//        if ($body instanceof \Closure) {
//            $r = new \ReflectionFunction($body);
//            $body = $r->getClosure();
//
//            if ($r->isGenerator()) {
//                $body = $body(self::$CHUNK_SIZE);
//
//                return $generatorToCallable($body);
//            }
//
//            return $body;
//        }

        return $body;
    }

    private static function jsonEncode(mixed $value, int $flags = null, int $maxDepth = 512): string
    {
        $flags ??= JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRESERVE_ZERO_FRACTION;

        $value = json_encode($value, $flags | JSON_THROW_ON_ERROR);
        if ($value === false){
            throw new \Exception("Ошибка при декодировании JSON");
        }
        return $value;
    }

    // работает
    /**
     * @param array $options
     * @param array $defaultOptions
     * @param bool $allowExtraOptions
     * @return array
     * @throws \Exception
     */
    public static function mergeDefaultOptions(array $options, array $defaultOptions, bool $allowExtraOptions = false): array
    {
        // сохраняется переформатированный массив передаваемых данных с помощью функции normalizedHeaders
        $options['normalized_headers'] = self::normalizeHeaders($options['headers'] ?? []);

        // Если основной массив данных определён, не равен false и null,
        // то ранее созданный массив объединяется с нормализованными по тому же правилу записями основного массива опций.
        // Причем при совпадающих ключах правый массив игнорируется.
        if ($defaultOptions['headers'] ?? false) {
            $options['normalized_headers'] += self::normalizeHeaders($defaultOptions['headers']);
        }

        // объединяет все подмассивы в один общий
        //$options['headers'] = array_merge(...array_values($options['normalized_headers'])) ?: [];
        $headers = $options['normalized_headers'];
        $mergedHeaders = [];
        foreach ($headers as $headerArray) {
            $mergedHeaders = array_merge($mergedHeaders, $headerArray);
        }
        $options['headers'] = $mergedHeaders ?: [];

        // resolve определяет, нужно ли cURL автоматически преобразовывать доменное имя хоста в IP-адрес.
        // resolve может содержать список доменных имен и соответствующих им IP-адресов.
        // Подобная информация, судя по всему, позволяет ускорить подключение.
        if ($resolve = $options['resolve'] ?? false) {
            $options['resolve'] = [];
            foreach ($resolve as $k => $v) {
                // Через parseUrl происходит разбиение url-адреса на части, его валидация, склеивание
                // передаваемых параметров с основными, а в конце возврат преобразованного url-адреса по частям
                // в качестве словаря.
                // В данном случае с помощью ключа 'authority' функция возвращает часть url-запроса, содержащего:
                // если есть сам хост, имя и пароль: username+password+host,
                // если есть хост и имя, но пароля нет: username+host,
                // если есть хост, нет имени и пароля (или есть пароль и нет имени): host,
                // если нет хоста: null.
                // Через substr(url, 2) в качестве ключа будет использоваться запись "username+pass+host", начиная с 3го символа.
                // В результате в массиве $options['resolve'] под ключом доменного имени будет записан IP-адрес сети.
                $options['resolve'][substr(self::parseUrl('http://'.$k)['authority'], 2)] = (string) $v;
            }
        }

        // Option "query" is never inherited from defaults
        $options['query'] ??= [];

        // объединение массивов
        $options += $defaultOptions;

        // если в полученном массиве данных есть пустые поля, они заполняются из массива emptyDefaults
        if (self::$emptyDefaults) {
            foreach (self::$emptyDefaults as $k => $v) {
                if (!isset($options[$k])) {
                    $options[$k] = $v;
                }
            }
        }

        if (isset($defaultOptions['extra'])) {
            $options['extra'] += $defaultOptions['extra'];
        }

        // если расшифровка доменных имен доступна и для массива по умолчанию, то объединенный массив
        // доменных имен и их IP обновляется.
        if ($resolve = $defaultOptions['resolve'] ?? false) {
            foreach ($resolve as $k => $v) {
                $options['resolve'] += [substr(self::parseUrl('http://'.$k)['authority'], 2) => (string) $v];
            }
        }

        if ($allowExtraOptions || !$defaultOptions) {
            return $options;
        }

        // Look for unsupported options
        foreach ($options as $name => $v) {
            if (\array_key_exists($name, $defaultOptions) || 'normalized_headers' === $name) {
                continue;
            }

            // эту часть кода надо переписать
            if ('auth_ntlm' === $name) {
                $msg = 'try using "%s" instead.';
                throw new \Exception(sprintf('Option "auth_ntlm" is not supported by "%s", '.$msg, __CLASS__, CurlHttpClient::class));
            }

            if ('vars' === $name) {
                throw new \Exception(sprintf('Option "vars" is not supported by "%s"', __CLASS__));
            }

            $alternatives = [];

            foreach ($defaultOptions as $k => $val) {
                if (levenshtein($name, $k) <= \strlen($name) / 3 || strpos($k, $name) !== false) {
                    $alternatives[] = $k;
                }
            }

            throw new \Exception(sprintf('Unsupported option "%s" passed to "%s", did you mean "%s"?', $name, __CLASS__, implode('", "', $alternatives ?: array_keys($defaultOptions))));
        }

        return $options;
    }

    // работает
    // наложил ограничения: в функцию в качестве заголовков могут передаваться только строковые типы данных
    /**
     * @param mixed $headers
     * @return string[][]
     * @throws \Exception
     */
    public static function normalizeHeaders(array $headers): array
    {
        /** @var string[][] $normalizedHeaders */
        $normalizedHeaders = [];
        foreach ($headers as $name => $values) {
            // если ключ является числом и при этом $value является строкой, нужно вытащить название поля из строки $value
            if (\is_int($name)) {
                if (!\is_string($values)) {
                    throw new \Exception(sprintf('Invalid value for header "%s": expected string, "%s" given.', $name, "string[]"));
                }
                [$name, $values] = explode(':', $values, 2);
                $values = [(string)ltrim($values)];
            }
            if (is_array($values)){
                if (!\is_string($values[0])){
                    throw new \Exception(sprintf('Invalid value for header "%s": expected string or string[].', $name));
                }
            }

            // далее мы перезаписываем элемент словаря "$name: [$value's]" по следующему правилу:
            // $name: [$name: $value, $name: $value и т.д.]
            $lcName = strtolower($name);
            $normalizedHeaders[$lcName] = [];

            foreach ($values as $value) {
                $normalizedHeaders[$lcName][] = $value = $name.': '.$value;

                if (\strlen($value) !== strcspn($value, "\r\n\0")) {
                    // throw new InvalidArgumentException(sprintf('Invalid header: CR/LF/NUL found in "%s".', $value));
                    throw new \Exception("Invalid header");
                }
            }
        }
        return $normalizedHeaders;
    }

    // работает
    /**
     * @param string $url
     * @param string[]|null $query
     * @param int[] $allowedSchemes
     * @return (?string)[]
     * @throws \Exception
     */
    public static function parseUrl(string $url, array $query = [], array $allowedSchemes = ['http' => 80, 'https' => 443]): array
    {
        // если встроенная функция не смогла разбить url по частям, то она возвращает false
        /** @var mixed $parts */
        $parts = parse_url($url);
        if (false === $parts) {
            // throw new InvalidArgumentException(sprintf('Malformed URL "%s".', $url));
            throw new \Exception("Malformed URL.");
        }

        // Если передаваемый параметр query существует, то мы перезаписываем часть url[query], объединяя эти два массива значений.
        // Ф-ция mergeQueryString вернёт строку параметров, которые, при наличии в обоих query очередях, будут перезаписаны из
        // передаваемого массива параметров $query. Им отдаётся предпочтение, т.к. флаг replace установлен в true.
        if ($query) {
            $parts['query'] = self::mergeQueryString((string)$parts['query'] ?? null, $query, true);
        }

        // записываем значение порта или 0, если его нет в url-запросе
        $port = $parts['port'] ?? 0;

        // если url-запрос содержит протокол подключения (http или https)
        /** @var string|null $scheme */
        $scheme = (string)$parts['scheme'] ?? null;
        if (null !== $scheme) {
            // если используемый параметр есть в словаре поддерживаемых протоколов, то всё ок, иначе ошибка
            if (!isset($allowedSchemes[$scheme = strtolower($scheme)])) {
                // throw new InvalidArgumentException(sprintf('Unsupported scheme in "%s".', $url));
                throw new \Exception("Unsupported scheme.");
            }

            // если ранее определенное значение порта из url совпадает с числом из словаря по ключу параметра schema,
            // то в port запишется 0, иначе запишется сам $port.
            $port = $allowedSchemes[$scheme] === $port ? 0 : $port;
            $scheme .= ':';
        }
        /** @var string|null $host */
        $host = (string)$parts['host'] ?? null;
        if (null !== $host) {
            $host .= $port ? ':'.$port : '';
        }

        // происходит окончательная валидация частей url-запроса
        foreach (['user', 'pass', 'path', 'query', 'fragment'] as $part) {
            if (!isset($parts[$part])) {
                continue;
            }

            // чтобы убедиться в корректности url-запроса, в том, что он не содержит неподдерживаемых символов,
            // все закодированные url-символы сначала декодируются в обычные символы, а после кодируются обратно.
            // if (str_contains($parts[$part], '%')) {
            if (strpos($parts[$part], '%') !== false){
                // https://tools.ietf.org/html/rfc3986#section-2.3
                $parts[$part] = preg_replace_callback('/%(?:2[DE]|3[0-9]|[46][1-9A-F]|5F|[57][0-9A]|7E)++/i', fn ($m) => rawurldecode($m[0]), $parts[$part]);
            }
            // теперь вся строка вновь кодируется
            // https://tools.ietf.org/html/rfc3986#section-3.3
            $parts[$part] = (string)preg_replace_callback("#[^-A-Za-z0-9._~!$&/'()[\]*+,;=:@\\\\^`{|}%]++#", fn ($m) => rawurlencode($m[0]), $parts[$part]);
        }

        return [
            // возврат протокола
            'scheme' => $scheme,
            // возврат склеенного (имени пользователя + пароль (если пароль есть))(если имя пользователь есть) и хоста
            // если хост не определен, возвращается null
            'authority' => null !== $host ? '//'.(isset($parts['user']) ? $parts['user'].(isset($parts['pass']) ? ':'.$parts['pass'] : '').'@' : '').$host : null,
            'path' => isset($parts['path'][0]) ? (string)$parts['path'] : null,
            isset($parts['query']) ? '?'.$parts['query'] : null,
            'fragment' => isset($parts['fragment']) ? '#'.$parts['fragment'] : null,
        ];
    }

    // работает
    /**
     * @param string|null $queryString
     * @param (string|null)[] $queryArray
     * @param bool $replace
     * @return string|null
     */
    public static function mergeQueryString(?string $queryString, array $queryArray, bool $replace): ?string
    {
        // данная функция используется при вызове конструктора для создания CurlClient.
        // она нужна при работе с определением IP-адреса доменного имени.
        // функция позволяет объединить передаваемые query параметры для создания url запроса и те,
        // что изначально содержатся в url и определяются с помощью встроенной функции url_parse.

        // таким образом, $queryString – это та самая query строка, которая определяется через url_parse, она может быть пустой. Будем называть её изначальной.
        // а $queryArray – это массив значений query, который дополнительно передаётся, он пустым быть по идее не должен.

        // если дополнительный массив параметров пуст, то объединять нечего и возврат очевиден
        if (!$queryArray) {
            return $queryString;
        }

        $query = [];
        // если изначальная строка query не пустая, нужно как-то её сложить в словарь для анализа
        if (null !== $queryString) {
            // здесь мы разбиваем строку на массив по разделителям
            foreach (explode('&', $queryString) as $v) {
                // только в случае, если полученная строка не является пустой
                if ('' !== $v) {
                    // urldecode позволяет преобразовать кодированные url символы в обычные человеческие буквы.
                    // в начале мы отделяем название переменной от её содержимой,
                    // далее декодируем закодированные url символы, а после записываем название параметра (ключ) в переменную $k.
                    $k = urldecode(explode('=', $v, 2)[0]);
                    // теперь можно создать новую переменную по такому ключу в $query.
                    // в случае, если такой ключ в массиве уже установлен, он будет
                    // склеен с рассматриваемой строкой $v формата "имя=значение" через символ &.
                    $query[$k] = (isset($query[$k]) ? $query[$k] . '&' : '') . $v;
                }
            }
        }

        // если флаг replace установлен, то мы убираем те параметры в query, которые отсутствуют в передаваемом массиве параметров
        if ($replace) {
            foreach ($queryArray as $k => $v) {
                if (null === $v) {
                    unset($query[$k]);
                }
            }
        }

        // записываем в переменную закодированную url последовательность параметров передаваемого массива
        $queryString = http_build_query($queryArray, '', '&', \PHP_QUERY_RFC3986);
        $queryArray = [];

        // далее, если полученная строка не пуста, происходит раскодировка некоторых закодированных символов
        // с помощью встроенной функции strtr.
        if ($queryString) {
//            if (str_contains($queryString, '%')) {
            if (strpos($queryString, '%') !== false){
                // https://tools.ietf.org/html/rfc3986#section-2.3 + some chars not encoded by browsers
                $queryString = strtr($queryString, [
                    '%21' => '!',
                    '%24' => '$',
                    '%28' => '(',
                    '%29' => ')',
                    '%2A' => '*',
                    '%2F' => '/',
                    '%3A' => ':',
                    '%3B' => ';',
                    '%40' => '@',
                    '%5B' => '[',
                    '%5C' => '\\',
                    '%5D' => ']',
                    '%5E' => '^',
                    '%60' => '`',
                    '%7C' => '|',
                ]);
            }

            // после декодировки неподдерживаемых символов, массив queryArray перезаписывается.
            // в цикле рассматривается каждый параметр формата "имя=значение" и в массив под ключом декодированного имени параметра
            // записывается сама строка "имя=значение" в url-формате (без полной декодировки).
            foreach (explode('&', $queryString) as $v) {
                $queryArray[rawurldecode(explode('=', $v, 2)[0])] = $v;
            }
        }
        // уже теперь полученный словарь параметров объединяется в строку с помощью встроенной функции emplode, используя между
        // элементами словаря разделитель '&'.
        // При чем, если флаг replace установлен, в строку преобразуется только массив переданных значений, в противном случае
        // будет использовано объединение массива и все совпадающие ключи будут использованы из $query (предпочтительным будет левый массив в выражении).
        return implode('&', $replace ? array_replace($query, $queryArray) : ($query + $queryArray));
    }
}