local medoid = {}

function medoid.access(prefix, host)
    -- ngx.header["Content-Type"] = "text/html"
    host = host or ngx.var.host

    local image_id = ngx.var.uri:gsub('/?image/', '')
    local query_str = ''

    if ngx.var.query_string then
        query_str = '&' .. ngx.var.query_string
    end

    ngx.var.target = string.format(
        '%s://%s/wp-admin/admin-ajax.php?action=medoid_view_file&id=%s%s',
        ngx.var.scheme,
        host,
        image_id,
        query_str
    );

    -- ngx.say(ngx.var.target)
    -- ngx.exit(ngx.HTTP_OK)
end

return medoid
