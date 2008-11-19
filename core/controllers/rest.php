<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2008 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
/**
 * This abstract controller makes it easy to create a RESTful controller.  To use it, create a
 * subclass which defines the resource type and implements get/post/put/delete methods, like this:
 *
 * class Comment_Controller extends REST_Controller {
 *   protected $resource_type = "comment";  // this tells REST which model to use
 *
 *   public function _index() {
 *     // Handle GET request to /controller
 *   }
 *
 *   public function _show(ORM $comment, $output_format) {
 *     // Handle GET request to /comments/{comment_id}
 *   }
 *
 *   public function _update(ORM $comment, $output_format) {
 *     // Handle PUT request to /comments/{comment_id}
 *   }
 *
 *   public function _create(ORM $comment, $output_format) {
 *     // Handle POST request to /comments
 *   }
 *
 *   public function _delete(ORM $comment, $output_format) {
 *     // Handle DELETE request to /comments/{comments_id}
 *   }
 *
 *   public function _form_add($parameters) {
 *     // Handle GET request to /form/add/comments
 *     // Show a form for creating a new comment
 *   }
 *
 *   public function _form_edit(ORM $comment) {
 *     // Handle GET request to /form/edit/comments
 *     // Show a form for editing an existing comment
 *   }
 *
 * A request to http://example.com/gallery3/comments/3 will result in a call to
 * REST_Controller::dispatch(3) which will load up the comment associated with id 3.  If there's
 * no such comment, it returns a 404.  Otherwise, it will then delegate to
 * Comment_Controller::get() with the ORM instance as an argument.
 */
abstract class REST_Controller extends Controller {
  protected $resource_type = null;

  public function dispatch($id) {
    if ($this->resource_type == null) {
      throw new Exception("@todo ERROR_MISSING_RESOURCE_TYPE");
    }

    // A request on /controller gets routed to REST_Controller::dispatch(0).
    if (!$id && $this->request_method() == "get") {
      return $this->_index();
    }

    // @todo this needs security checks
    $resource = ORM::factory($this->resource_type, $id);
    if (!$resource->loaded && !$this->request_method() == "post") {
      return Kohana::show_404();
    }

    if ($this->request_method() == "get") {
      $this->_show($resource, $this->output_format());

      if (Session::instance()->get("use_profiler", false)) {
        $profiler = new Profiler();
        $profiler->render();
      }
      return;
    }

    switch ($this->request_method()) {
    case "put":
      return $this->_update($resource);

    case "delete":
      return $this->_delete($resource);

    case "post":
      return $this->_create($resource);
    }
  }

  /* We're editing an existing item, load it from the database. */
  public function form_edit($resource_id) {
    if ($this->resource_type == null) {
      throw new Exception("@todo ERROR_MISSING_RESOURCE_TYPE");
    }

    // @todo this needs security checks
    $resource = ORM::factory($this->resource_type, $resource_id);
    if (!$resource->loaded) {
      return Kohana::show_404();
    }

    return $this->_form_edit($resource);
  }

  /* We're adding a new item, pass along any additional parameters. */
  public function form_add($parameters) {
      return $this->_form_add($parameters);
  }

  /**
   * We're expecting to run in an environment that only supports GET/POST, so expect to tunnel
   * PUT and DELETE through POST.
   *
   * Returns the HTTP request method taking into consideration PUT/DELETE tunneling.
   * @todo Move this to a MY_request helper?
   * @return string HTTP request method
   */
  protected function request_method() {
    if (request::method() == "get") {
      return "get";
    } else {
      switch ($this->input->post("_method", $this->input->get("_method"))) {
      case "put":    return "put";
      case "delete": return "delete";
      default:       return "post";
      }
    }
  }

  /**
   * Choose an output format based on what the client prefers to accept.
   * @return string "html", "xml" or "json"
   */
  protected function output_format() {
    // Pick a format, but let it be overridden.
    return $this->input->get(
      "_format", $this->input->post(
        "_format", request::preferred_accept(
          array("html", "xml", "json"))));
  }

  /**
   * Perform a GET request on the controller root
   * (e.g. http://www.example.com/gallery3/comments)
   */
  abstract public function _index();

  /**
   * Perform a POST request on this resource
   * @param ORM $resource the instance of this resource type
   */
  abstract public function _create($resource);

  /**
   * Perform a GET request on this resource
   * @param ORM $resource the instance of this resource type
   */
  abstract public function _show($resource, $output_format);

  /**
   * Perform a PUT request on this resource
   * @param ORM $resource the instance of this resource type
   */
  abstract public function _update($resource);

  /**
   * Perform a DELETE request on this resource
   * @param ORM $resource the instance of this resource type
   */
  abstract public function _delete($resource);

  /**
   * Present a form for adding a new resource
   * @param string part of the URI after the controller name
   */
  abstract public function _form_add($parameter);

  /**
   * Present a form for editing an existing resource
   * @param ORM $resource the resource container for instances of this resource type
   */
  abstract public function _form_edit($resource);
}
