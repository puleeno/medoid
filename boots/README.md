
# Setup

## Openresty

Added new location for server

```
location ~ ^/images/.* {
    resolver 8.8.8.8 ipv6=off;
    server_tokens off;

    set $target '';
    set $cdn_host '';
    set $app_root $realpath_root;

    access_by_lua_block {
        require('medoid'):access('/images');
    }

    proxy_pass $target;
    proxy_redirect off;

    proxy_set_header        Host $cdn_host;
    proxy_set_header        X-Real-IP $remote_addr;
    proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header        X-Forwarded-Proto $scheme;
}```

## Lua script

Copy file openresty to Lua lib directories

