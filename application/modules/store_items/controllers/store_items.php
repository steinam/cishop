<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Store_items extends MX_Controller {

    function __construct() {
        parent::__construct();
        $this->model = 'mdl_store_items';
        $this->load->model($this->model);

        $this->load->library('form_validation');
    }

    function manage() {
        Modules::run('site_security/_is_admin');
        $this->session->set_userdata('test', 'test');
        $data['view_module'] = 'store_items';
        $data['view_file'] = "manage";
        $data['items'] = $this->get('title');
        echo Modules::run('templates/admin', $data);
    }

    # TODO: work on optimizing. consider having separate funciton to load form and to add/update data
    function create($id = null) {
        Modules::run('site_security/_is_admin');

        $submit = $this->input->post('submit', TRUE);

        if ($submit == "cancel") {
            redirect('store_items/manage');
        }

        if ($submit == "submit") {

            $this->form_validation->set_rules('title', 'Item Title', 'required|max_length[240]|callback_item_check'); // TODO: change callback to is_unique???
            $this->form_validation->set_rules('price', 'Item Price', 'required|numeric');
            $this->form_validation->set_rules('was_price', 'Previous Price', 'numeric');
            $this->form_validation->set_rules('status', 'Status', 'required|numeric');
            $this->form_validation->set_rules('description', 'Item Description', 'required');

            if ($this->form_validation->run($this) == TRUE) {
                $data = $this->fetch_data_from_post();
                $data['url'] = url_title($data['title']);

                if (is_numeric($id)) {
                    // update item
                    $this->_update($id, $data);
                    $value = "<div class='alert alert-success' role='alert'>The item details were successfully updated.</div>";
                    $this->session->set_flashdata('item', $value);
                } else {
                    // insert item
                    $this->_insert($data);
                    $update_id = $this->get_max();

                    $value = "<div class='alert alert-success' role='alert'>The item was successfully added.</div>";
                    $this->session->set_flashdata('item', $value);
                    redirect("store_items/create/$update_id");
                }
            }
        }

        if ((is_numeric($id)) && ($submit != "submit")) {
            $data = $this->fetch_data_from_db($id);
        } else {
            $data = $this->fetch_data_from_post();
        }

        if (!is_numeric($id)) {
            $data['headline'] = "Create New Item";
        } else {
            $data['headline'] = "Update Item Details";
        }

        $data['update_id'] = $id;
        $data['view_module'] = 'store_items';
        $data['view_file'] = "create";

        echo Modules::run('templates/admin', $data);
    }

    function fetch_data_from_db($id) {
        $query = $this->get_where($id);
        foreach($query->result() as $row) {
            $data['title'] = $row->title;
            $data['url'] = $row->url;
            $data['price'] = $row->price;
            $data['description'] = $row->description;
            $data['big_pic'] = $row->big_pic;
            $data['small_pic'] = $row->small_pic;
            $data['was_price'] = $row->was_price;
            $data['status'] = $row->status;
        }

        if (!isset($data)) {
            $data = "";
        }

        return $data;
    }

    function fetch_data_from_post() {
        $data['title'] = $this->input->post('title', TRUE);
        $data['price'] = $this->input->post('price', TRUE);
        $data['was_price'] = $this->input->post('was_price', TRUE);
        $data['description'] = $this->input->post('description', TRUE);
        $data['status'] = $this->input->post('status', TRUE);

        return $data;
    }

    function get($order_by) {
        $query = $this->{$this->model}->get($order_by);
        return $query;
    }

    function get_with_limit($limit, $offset, $order_by) {
        $query = $this->{$this->model}->get_with_limit($limit, $offset, $order_by);
        return $query;
    }

    function get_where($id) {
        $query = $this->{$this->model}->get_where($id);
        return $query;
    }

    function get_where_custom($col, $value) {
        $query = $this->{$this->model}->get_where_custom($col, $value);
        return $query;
    }

    function _insert($data) {
        $this->{$this->model}->_insert($data);
    }

    function _update($id, $data) {
        $this->{$this->model}->_update($id, $data);
    }

    function _delete($id) {
        $this->{$this->model}->_delete($id);
    }

    function count_where($column, $value) {
        $count = $this->{$this->model}->count_where($column, $value);
        return $count;
    }

    function get_max() {
        $max_id = $this->{$this->model}->get_max();
        return $max_id;
    }

    function _custom_query($mysql_query) {
        $query = $this->{$this->model}->_custom_query($mysql_query);
        return $query;
    }

    // Callbacks
    function item_check($str) {
        $item_url = url_title($str);
        $update_id = $this->uri->segment(3);
        $query = "SELECT * FROM store_items WHERE title = '$str' and url = '$item_url'";

        if (is_numeric($update_id)) {
            $query .= " and id != '$update_id'";
        }

        $query = $this->_custom_query($query);
        $num_rows = $query->num_rows();

        if ($num_rows > 0) {
                $this->form_validation->set_message('item_check', "The {field} '$str' is already used. Enter a different one.");
                return FALSE;
        } else {
                return TRUE;
        }
    }

}