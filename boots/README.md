
# Setup

## Openresty

Added new location for server

```
location ~ /images/.* {
    set $target '';
    resolver 8.8.8.8 ipv6=off;

    server_tokens off;
    lua_code_cache off;

    set $app_root $realpath_root;

    access_by_lua_block {
        require('medoid'):access('/images');
    }
    proxy_pass $target;
    proxy_redirect off;
}```

## Lua script

Copy file openresty to Lua lib directories

