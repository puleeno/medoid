local medoid = {}

function medoid.access(prefix, host)
    -- ngx.header["Content-Type"] = "text/html"
    host = host or ngx.var.host

    local image_id = ngx.var.request_uri

    ngx.var.target = string.format('%s://%s', ngx.var.scheme, host);

    ngx.say(ngx.var.target)
    ngx.exit(ngx.HTTP_OK)
end

return medoid
