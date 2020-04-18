local DBI = require("DBI")

local medoid = {
    configs = {
        db = {}
    },
    connection = nil,
    use_placeholder = true
}

function medoid.parse_config_file(self, app_root)
    local wp_config_file = string.format("%s/wp-config.php", app_root)
    local wp_config = io.open(wp_config_file, "r")

    if wp_config ~= nil then
        local all_configs = wp_config:read("*all")
        local db_configs = all_configs:gmatch("define%(%s?'(DB_[^']+)',%s?'([^']+)")
        for db_key, config_value in db_configs do
            if db_key == "DB_NAME" then
                self.configs.db.name = config_value
            elseif db_key == "DB_USER" then
                self.configs.db.user = config_value
            elseif db_key == "DB_PASSWORD" then
                self.configs.db.password = config_value
            elseif db_key == "DB_HOST" then
                self.configs.db.host = config_value
            elseif db_key == "DB_CHARSET" then
                self.configs.db.charset = config_value
            end
        end

        local db_prefix = all_configs:match("$table_prefix%s?=%s?'([^']+)")
        self.configs.db.prefix = db_prefix
    else
        ngx.exit(ngx.HTTP_INTERNAL_SERVER_ERROR)
    end
end

function medoid.open_db(self)
    self.connection =
        assert(
        DBI.Connect("MySQL", self.configs.db.name, self.configs.db.user, self.configs.db.password, self.configs.db.host)
    )
end

function medoid.close_db(self)
    if self.connection == nil then
        return
    end

    self.connection:close()
end

function medoid.get_image(self, proxy_image_id)
    local sth =
        assert(
        self.connection:prepare(
            [[
                SELECT
                    i.post_id,
                    i.image_url,
                    s.image_url AS image_size_url,
                    p.guid,
                    i.cdn_image_url,
                    s.cdn_image_url as cdn_image_size_url
                FROM
                    lc_medoid_images i
                    LEFT JOIN lc_medoid_image_sizes s ON s.image_id = i.ID
                    INNER JOIN lc_posts p ON p.ID = i.post_id
                WHERE
                    i.proxy_id = ?
                    OR s.proxy_id = ?
            ]]
        )
    )

    -- execute select with a bind variable
    sth:execute(proxy_image_id, proxy_image_id)

    -- iterate over the returned data
    return sth:fetch()
end

function medoid.placeholder_image(self)
end

function medoid.access(self, prefix, host)
    ngx.header["Content-Type"] = "text/html"

    self.parse_config_file(self, ngx.var.app_root)
    self.open_db(self)

    local image_id = ngx.var.uri:gsub(prefix .. "/", "")
    local image = self.get_image(self, image_id)
    local image_url = ""

    if image == nil then
        if self.configs.use_placeholder then
            image_url = self:placeholder_image()
        else
            self.close_db(self)
            ngx.exit(ngx.HTTP_NOT_FOUND)
        end
    end

    if image[6] ~= "" then -- Check cdn image size URL
        image_url = image[6]
    elseif image[3] ~= "" then -- Check image size URL
        image_url = image[3]
    elseif image[5] ~= "" then -- Check cdn image URL
        image_url = image[5]
    elseif image[2] ~= "" then -- Check image URL
        image_url = image[2]
    else
        image_url = image[4] -- Use guid if all image URL is not exists
    end

    if ngx.var.query_string then
        image_url = image_url .. "&" .. ngx.var.query_string
    end

    self.close_db(self)
    ngx.var.target = image_url
end

return medoid
