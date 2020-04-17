require("DBI")

local medoid = {
    configs = {
        db = {}
    },
    use_placeholder = true,
    connection = nil
}

function medoid.open_db(self)
    
end

function medoid.parse_config_file(self, app_root)
    local wp_config = io.open(string.format("%s/wp-config.php", app_root), "r")
    if wp_config ~= nil then
        local all_configs = wp_config:read("*all")
        local db_configs = all_configs:gmatch("define%(%s?'(DB_[^']+)',%s?'([^']+)")
        for db_key, config_value in db_configs do
            if db_key == "DB_NAME" then
                self.configs.db.name = config_value
            elseif db_key == "DB_USER" then
                self.configs.db.user = config_value
            elseif db_key == "DB_PASSWORD" then
                self.configs.db.pass = config_value
            elseif db_key == "DB_HOST" then
                self.configs.db.host = config_value
            elseif db_key == "DB_CHARSET" then
                self.configs.db.charset = config_value
            end
        end
    else
        ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
    end
end

function medoid.close_db(self)
end

function medoid.get_image(self, proxy_image_id)
    return true
end

function medoid.placeholder_image(self)
end

function medoid.access(self, realpath_root, prefix, host)
    ngx.header["Content-Type"] = "text/html"

    self.parse_config_file(self, ngx.var.app_root)
    self.open_db(self)

    local image_id = ngx.var.uri:gsub("/?image/", "")
    local image = self.get_image(self,image_id)
    local image_url = ''

    if image == nil then
        if self.configs.use_placeholder then
            image_url = self:placeholder_image()
        else
            ngx.exit(ngx.HTTP_NOT_FOUND)
        end
    end

    local query_str = ""
        if ngx.var.query_string then
            query_str = "&" .. ngx.var.query_string
        end

    -- ngx.say(ngx.var.target)
    -- ngx.exit(ngx.HTTP_OK)

    -- ngx.var.target = 'https://www.google.com/logos/doodles/2020/thank-you-teachers-and-childcare-workers-6753651837108762.3-law.gif'
end

return medoid
