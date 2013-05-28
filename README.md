# Kiwigrid AppService

This a small helper library for using the Kiwigrid Web API within a Symfony 2.1+ application.

## API Dokumentation

The REST API Dokumentation can be found under <http://developers.kiwigrid.com/webapi/5.6/>

## Symfony Integration

In the global config.yml the following must be added:

    # local dev config
    parameters:
        app_service.ssl_certs_path:  "%kernel.root_dir%/../certs" #the path to the folder that contain the cert and key file
        app_service.ssl_cert_file:   "cert_%kernel.environment%.pem"                   #the filename of the cert file (pem format)
        app_service.ssl_key_file:    "key_%kernel.environment%.pem"                    #the filename of the key file (pem format)
        app_service.ca_cert_file:    "root.kiwigrid.com.pem"                    #the filename of the ca cert file (pem format)
        app_service.base_url:        "https://webapi.dev.kiwigrid.com/"
        
        
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

In the environment specific configs the following must be added:

    parameters:
        app_service.base_url:        "https://webapi.kiwigrid.com/" #enviroment specific API URL
        app_service.ssl_password:    "mySSLKeyPassword"
        
## Requirements

To use the API you will need a Kiwigrid Developer Certificate.