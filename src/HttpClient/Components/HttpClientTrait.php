<?php
namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Components\Exception\InvalidArgumentException;
use Kaa\HttpClient\Components\Exception\TransportException;

require_once __DIR__.'/PredefinedConstants.php';

class HttpClientTrait
{
    public static int $CHUNK_SIZE = 16372;
    private static Options $emptyDefaults;

    /**
     * @param ?string $method
     * @param ?string $url
     * @param Options $options
     * @param ?Options $defaultOptions
     * @param bool $allowExtraOptions
     * @return Options
     */
    public static function prepareRequest(?string $method, ?string $url, Options $options, ?Options $defaultOptions, bool $allowExtraOptions = false): Options
    {
        self::$emptyDefaults = new Options();

        if (null !== $method) {
            // здесь проверяется, что метод содержит только буквы верхнего регистра
            if (\strlen($method) !== strspn($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')) {
                throw new InvalidArgumentException(sprintf('Invalid HTTP method "%s", only uppercase letters are accepted.', $method));
            }
            if (!$method) {
                throw new InvalidArgumentException('The HTTP method cannot be empty.');
            }
        }

        if ($defaultOptions){
            $options = self::mergeDefaultOptions($options, $defaultOptions, $allowExtraOptions);
        }

        if (Options::isset($options->getJson())) {
            if (Options::isset($options->getBody()) && '' !== $options->getBody()) {
                throw new InvalidArgumentException('Define either the "json" or the "body" option, setting both is not supported.');
            }
            $options->setBody(self::jsonEncode($options->getJson()));
            $options->setJson('');

            if (!Options::isset($options->getNormalizedHeader('content-type'))) {
                $options->setNormalizedHeader('content-type', ['Content-Type: application/json']);
            }
        }

        if (!Options::isset($options->getNormalizedHeader('accept'))) {
            $options->setNormalizedHeader('accept', ['Accept: */*'] );
        }

        if (Options::isset($options->getBody())) {
            if (
                \is_array($options->getBody())
                && (!is_bool($nHeaderContentType = $options->getNormalizedHeader('content-type')) ? (
                    $nHeaderContentType !== [] ? (
                        Options::isset($nHeaderContentType[0])
                        || !strpos($nHeaderContentType[0], 'application/x-www-form-urlencoded')
                    ) : true
                ) : true)
            ) {
                $options->setNormalizedHeader('content-type', ['Content-Type: application/x-www-form-urlencoded']);
            }

            #ifndef KPHP
            if (is_string($body = $options->getBody())
                && ((string) strlen($body) !== substr(($h = !is_bool($nHeaderContentType) ? ($nHeaderContentType !== [] ? (Options::isset($nHeaderContentType[0]) ? $nHeaderContentType[0] : ''): '') : ''), 16))
                && ('' !== $h || '' !== $body)
            ) {
                if ('chunked' === substr((!is_bool($nTransferEncoding = $options->getNormalizedHeader('transfer-encoding')) ? ($nTransferEncoding !== [] ? (Options::isset($nTransferEncoding[0]) ? $nTransferEncoding[0] : ''): '') : ''), \strlen('Transfer-Encoding: '))){
                    $options->setNormalizedHeader('transfer-encoding', []);
                    $options->setBody(self::dechunk($body));
                }

                $options->setNormalizedHeader('content-length', [substr_replace($h ?: 'Content-Length: ', \strlen($options->getBody()), 16)]);
            }
            #endif
        }

        if (Options::isset($peerFingerprint = $options->getPeerFingerprint())){
            $options->setPeerFingerprint(self::normalizePeerFingerprint($peerFingerprint));
        }

        if (Options::isset($options->getAuthBearer())){
            if (preg_match('{[^\x21-\x7E]}', $options['auth_bearer'])) {
                throw new InvalidArgumentException('Invalid character found in option "auth_bearer": '.json_encode($options['auth_bearer']).'.');
            }
        }

        if (Options::isset($options->getAuthBasic()) && Options::isset($options->getAuthBearer())) {
            throw new InvalidArgumentException('Define either the "auth_basic" or the "auth_bearer" option, setting both is not supported.');
        }

        return $options;
    }

    /** @return mixed */
    private static function normalizePeerFingerprint(mixed $fingerprint): array
    {
        if (\is_string($fingerprint)) {
            switch (\strlen($fingerprint = str_replace(':', '', $fingerprint))) {
                case (32):
                    $fingerprint = ['md5' => $fingerprint];
                    break;
                case (40):
                    $fingerprint = ['sha1' => $fingerprint];
                    break;
                case (44):
                    $fingerprint = ['pin-sha256' => [$fingerprint]];
                    break;
                case (64):
                    $fingerprint = ['sha256' => $fingerprint];
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Cannot auto-detect fingerprint algorithm for "%s".', $fingerprint));
            }
        } elseif (\is_array($fingerprint)) {
            foreach ($fingerprint as $algo => $hash) {
                $fingerprint[$algo] = 'pin-sha256' === $algo ? (array) $hash : str_replace(':', '', $hash);
            }
        } else {
            throw new InvalidArgumentException(sprintf('Option "peer_fingerprint" must be string or array, "%s" given.', gettype($fingerprint)));
        }

        return $fingerprint;
    }

    #ifndef KPHP
    private static function dechunk(string $body): string
    {
        $h = fopen('php://temp', 'w+');
        stream_filter_append($h, 'dechunk', \STREAM_FILTER_WRITE);
        fwrite($h, $body);
        $body = stream_get_contents($h, -1, 0);
        rewind($h);
        ftruncate($h, 0);

        if (fwrite($h, '-') && '' !== stream_get_contents($h, -1, 0)) {
            throw new TransportException('Request body has broken chunked encoding.');
        }

        return $body;
    }
    #endif

    private static function jsonEncode(mixed $value, int $flags = null): string
    {
        $flags ??= \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_PRESERVE_ZERO_FRACTION;
        $nValue = json_encode($value, $flags | \JSON_THROW_ON_ERROR);
        if (is_bool($nValue)){
            throw new InvalidArgumentException('Invalid value for "json" option:' .$value);
        }

        return $nValue;
    }

    public static function mergeDefaultOptions(Options $options, Options $defaultOptions, bool $allowExtraOptions = false): Options
    {
        $options->setNormalizedHeaders(self::normalizeHeaders($options->getHeaders()));

        if (Options::isset($defaultOptions->getHeaders())) {
            $options->setNormalizedHeaders($options->getNormalizedHeaders() + self::normalizeHeaders($defaultOptions->getHeaders()));
        }

        /** @var array<int, array<string>> $tempHeaders */
        $tempHeaders = array_values($options->getNormalizedHeaders());

        /**
         * @param array<int,string> ...$arrays
         * @return array<int, string>
         */
        $mergeHeaders = static function(...$arrays) {
            $result = array();
            foreach ($arrays as $array) {
                foreach ($array as $key => $value) {
                    if (is_int($key)) {
                        $result[] = $value;
                    } else {
                        $result[$key] = $value;
                    }
                }
            }
            return $result;
        };

        $options->setHeaders($mergeHeaders(...$tempHeaders ?: [[]]));

        if (Options::isset($resolve = $options->getResolve())) {
            $options->setResolve([]);
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
                $key = substr((string)self::parseUrl('http://'.$k)['authority'], 2);
                if (is_bool($key)){
                    throw new InvalidArgumentException("Ошибка при работе parseUrl() для ключа " . $k);
                }
                $options->addToResolve($key, $v);
            }
        }

        if (!Options::isset($options->getQuery()))
            $options->setQuery([]);

        $options = Options::mergeOptions($options, $defaultOptions);
        if (isset(self::$emptyDefaults))
            $options = Options::mergeOptions($options, self::$emptyDefaults);

        if (Options::isset($tempExtra = $defaultOptions->getExtra())) {
            $options->setExtra($options->getExtra() + $tempExtra);
        }

        return $options;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, array<string>>
     */
    private static function normalizeHeaders(array $headers): array
    {
        /** @var array<string, array<string>> $normalizedHeaders */
        $normalizedHeaders = [[]];

        foreach ($headers as $name => $values) {
            // мы перезаписываем элемент словаря "$name: [$value's]" по следующему правилу:
            // $name: [$name: $value, $name: $value и т.д.]
            $lcName = strtolower($name);
            $normalizedHeaders[$lcName] = [];

            foreach ([$values] as $value) {
                $normalizedHeaders[$lcName][] = $value = $name.': '.$value;

                if (\strlen($value) !== strcspn($value, "\r\n\0")) {
                    throw new InvalidArgumentException(sprintf('Invalid header: CR/LF/NUL found in "%s".', $value));
                }
            }
        }

        return $normalizedHeaders;
    }


    //работает
    /**
     * @param string $url
     * @param array<string> $query
     * @param array<string, int> $allowedSchemes
     * @return mixed
     * @throws \Exception
     */
    public static function parseUrl(string $url, array $query = [], array $allowedSchemes = ['http' => 80, 'https' => 443]): array
    {
        // если встроенная функция не смогла разбить url по частям, то она возвращает false
        /** @var mixed $parts */
        $parts = parse_url($url);
        if (false === $parts) {
            // throw new InvalidArgumentException(sprintf('Malformed URL "%s".', $url));
            throw new InvalidArgumentException("Malformed URL.");
        }

        // Если передаваемый параметр query существует, то мы перезаписываем часть url[query], объединяя эти два массива значений.
        // Ф-ция mergeQueryString вернёт строку параметров, которые, при наличии в обоих query очередях, будут перезаписаны из
        // передаваемого массива параметров $query. Им отдаётся предпочтение, т.к. флаг replace установлен в true.
        if ($query !== []) {
            $parts['query'] = self::mergeQueryString((string)$parts['query'] ?? '', $query, true);
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
                throw new InvalidArgumentException("Unsupported scheme.");
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
     * @param string $queryString
     * @param string[] $queryArray
     * @param bool $replace
     * @return string
     */
    public static function mergeQueryString(string $queryString, array $queryArray, bool $replace): string
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
        if ($queryString !== '') {
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
                if (!$v) {
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