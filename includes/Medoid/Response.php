<?php
namespace Medoid;

class Response
{
    protected $provider_id;
    protected $provider_image_id;

    protected $status;
    protected $url;
    protected $data;
    protected $errors;

    public function __construct($provider, $status = false, $url = '', $data = array())
    {
        $this->provider_id = $provider;

        $this->status = $status;
        $this->url    = $url;
        $this->data   = $data;
    }

    public function get_provider_id()
    {
        return $this->provider_id;
    }

    public function set_provider_image_id($id)
    {
        $this->provider_image_id = $id;
    }

    public function get_provider_image_id()
    {
        return $this->provider_image_id;
    }

    public function set_status($success)
    {
        $this->status = (bool) $success;
    }

    public function get_status()
    {
        return (bool) $this->status;
    }

    public function set_url($url)
    {
        $this->url = $url;
    }

    public function get_url()
    {
        return $this->url;
    }

    public function set_data($data)
    {
        if (empty($this->data)) {
            $this->data = $data;
        } else {
            $this->data = array_merge($this->data, $data);
        }
    }

    public function set($data_key, $data_value)
    {
        $this->data[ $data_key ] = $data_value;
    }

    public function get($data_key, $default_value = false)
    {
    }


    public function set_error($error)
    {
        if ($error instanceof \Exception) {
            $this->errors[] = $error->getMessage();
        } else {
            $this->errors[] = $error;
        }
    }

    public function set_errors($errors)
    {
        foreach ($errors as $error) {
            $this->set_error($error);
        }
    }

    public function get_error_message()
    {
        return array_get($this->errors, 0, '');
    }

    public function get_error_messages()
    {
        return $this->errors;
    }
}
