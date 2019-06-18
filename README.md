# opnsense-import-certificate
Script to import an SSL certificate into a running opnsense system

## usage


```
Usage: php opnsense-import-certificate.php /path/to/certificate.crt /path/to/private/key.pem cert_hostname.domain.tld
```

## automation example with dehydrated acme

```
./dehydrated -c -f config-lan
# push cert to opnsense
scp opnsense-import-certificate.php certs/yourhost.domain.tld/privkey.pem certs/yourhost.domain.tld/fullchain.pem root@youropnsensehost: \
&& ssh root@youropnsensehost 'php opnsense-import-certificate.php /root/fullchain.pem /root/privkey.pem yourhost.domain.tld'
```
