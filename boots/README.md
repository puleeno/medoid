
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
}```

## Lua script

Copy file openresty to Lua lib directories

