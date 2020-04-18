
# Setup

## Openresty

Added new location for server

```
location ~ /images/.* {
    server_tokens off;
    resolver 8.8.8.8 ipv6=off;

    set $target '';
    set $app_root $realpath_root;

    access_by_lua_block {
        require('medoid'):access('/images');
    }

    proxy_pass $target;
    proxy_redirect off;

    proxy_set_header        Host $host;
    proxy_set_header        X-Real-IP $remote_addr;
    proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header        X-Forwarded-Proto $scheme;
}```

## Lua script

Copy file openresty to Lua lib directories

