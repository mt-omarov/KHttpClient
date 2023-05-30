# `KHttpClient`
The project aims to port the `Symfony/HttpClient` library ([here is a link](https://symfony.com/doc/current/http_client.html)). 

## Main problems and challenges
`KPHP` is a translator for `PHP` code into `C++`. That is, in order to support the library, the code must be compilable, not strictly interpretable.\
This implies:
1. Using strict typing.
2. Prohibiting mixing of complex data and avoiding mixing of different data types in principle.
3. The implementation of unsupported functions.
4. Avoiding conflicts so that the library can run on both `PHP` and `KPHP`.

To start with, `CurlHttpClient` will be ported. \
`KPHP` originally supports many (almost all necessary) functions of the cURL library: cURL in `KPHP` is the same cURL, but in a wrapper. However, there are some things that are still missing there (setting server response event handlers, `curl_pause()` and others). This will prevent a full transfer of the library, but a test version can still be created.
## `CurlHttpClient`
Without going into detail, the main components of this module are the following:
* CurlResponse - query unit,
* CurlClientState - state machine,
* CurlHttpClient - module class itself.

> Note that the library in its first version will not be able to support modern HTTP/2 connections, because it cannot use the functionality of the server response handlers
