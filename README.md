# Kiwigrid AppService

This a small helper library for using the Kiwigrid Web API within a Symfony 2.1+ application.

## API Dokumentation

The REST API Dokumentation can be found under <http://developers.kiwigrid.com/webapi/5.6/>

## Symfony Integration

In the local parameter.yml the following must be added:

```
parameters:
  app_service.ssl_certs_path:  "%kernel.root_dir%/../certs"               #the path to the folder that contain the cert and key file
  app_service.ssl_cert_file:   "kiwigrid.myapp.crt"                       #the filename of the cert file (pem format)
  app_service.ssl_key_file:    "kiwigrid.myapp.key"                       #the filename of the key file (pem format)
  app_service.ca_cert_file:    "ca.crt"                                   #the filename of the ca cert file (pem format)
  app_service.base_url:        "https://webapi.dev.kiwigrid.com/"
  app_service.ssl_password:    "ChangeToActualKeyPassword"
```

In the global config.yml the following must be added:

```
services:
  session:
    class:          AppService\Core\SymfonyAppserviceSessionHandler

  app_service:
    class:          AppService\Core\AppService
    scope:          request
    arguments:
      - @session
      - "%app_service.ssl_certs_path%/%app_service.ssl_cert_file%"
      - "%app_service.ssl_certs_path%/%app_service.ssl_key_file%"
      - "%app_service.ssl_password%"
      - "%app_service.ssl_certs_path%/%app_service.ca_cert_file%"
      - "%app_service.base_url%"
      - @request
```

## Requirements

To use the API you will need a Kiwigrid Developer Certificate.

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

## License

The MIT License (MIT)

Copyright (c) 2013 [Kiwigrid GmbH](http://www.kiwigrid.com)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.