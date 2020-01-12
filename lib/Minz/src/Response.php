<?php

namespace Minz;

/**
 * The Response represents the answer given to a Request, and returned to the
 * user.
 *
 * A view can be attached to a Response. This view is destined to generate the
 * output to return to the user.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Response
{
    /** @var integer[] */
    public const VALID_HTTP_CODES = [
        100, 101,
        200, 201, 202, 203, 204, 205, 206,
        300, 301, 302, 303, 304, 305, 306, 307,
        400, 401, 402, 403, 404, 405, 406, 407, 408, 409,
        410, 411, 412, 413, 414, 415, 416, 417,
        500, 501, 502, 503, 504, 505,
    ];

    /**
     * Create a successful response (HTTP 200) with a View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ViewError
     *
     * @return \Minz\Response
     */
    public static function ok($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(200, $view);
    }

    /**
     * Create an accepted response (HTTP 202) with a View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ViewError
     *
     * @return \Minz\Response
     */
    public static function accepted($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(202, $view);
    }

    /**
     * Create a bad request response (HTTP 400) with a View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ViewError
     *
     * @return \Minz\Response
     */
    public static function badRequest($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(400, $view);
    }

    /**
     * Create a not found response (HTTP 404) with a View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ViewError
     *
     * @return \Minz\Response
     */
    public static function notFound($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(404, $view);
    }

    /**
     * Create an internal server error response (HTTP 500) with a View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ViewError
     *
     * @return \Minz\Response
     */
    public static function internalServerError($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(500, $view);
    }

    /**
     * Create a Response from a HTTP status code.
     *
     * @param integer $code The HTTP code to set for the response
     * @param \Minz\View $view The view to set to the response (optional)
     *
     * @throws \Minz\Errors\ResponseError if the code is not a valid HTTP status code
     */
    public function __construct($code, $view = null)
    {
        $this->setCode($code);
        $this->setView($view);
        if ($view) {
            $content_type = $view->contentType();
        } else {
            $content_type = 'text/plain';
        }
        $this->setHeader('Content-Type', $content_type);
    }

    /** @var integer */
    private $code;

    /** @var string[] */
    private $headers = [];

    /** @var \Minz\View */
    private $view;

    /**
     * @return \Minz\View The current view
     */
    public function view()
    {
        return $this->view;
    }

    /**
     * @param \Minz\View $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * @return integer The current HTTP code
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * @param integer $code
     *
     * @throws \Minz\Errors\ResponseError if the code is not a valid HTTP status code
     *
     * @return void
     */
    public function setCode($code)
    {
        if (!in_array($code, self::VALID_HTTP_CODES)) {
            throw new Errors\ResponseError("{$code} is not a valid HTTP code.");
        }

        $this->code = $code;
    }

    /**
     * Add or replace a HTTP header.
     *
     * @param string $header
     * @param string $value
     *
     * @return void
     */
    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * @return string[] The list of actual headers
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Generate and return the content from the view.
     *
     * @return string Return the view output, or an empty string if no views
     *                are attached.
     */
    public function render()
    {
        if ($this->view) {
            return $this->view->render();
        } else {
            return '';
        }
    }
}
