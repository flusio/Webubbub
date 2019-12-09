<?php

namespace Minz;

/**
 * The Response represents the answer given to a Request, and returned to the
 * user.
 *
 * A view filename is attached to a Response. This file should contain the HTML
 * or PHP+HTML code to return to the user. It is generally a `.phtml` file.
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
     * Create a successful response (HTTP 200).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_filename
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function ok($view_filename, $variables = [])
    {
        return Response::fromCode(200, $view_filename, $variables);
    }

    /**
     * Create an accepted response (HTTP 202).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_filename
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function accepted($view_filename, $variables = [])
    {
        return Response::fromCode(202, $view_filename, $variables);
    }

    /**
     * Create a bad request response (HTTP 400).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_filename Default is errors/bad_request.phtml
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function badRequest($view_filename = 'errors/bad_request.phtml', $variables = [])
    {
        return Response::fromCode(400, $view_filename, $variables);
    }

    /**
     * Create a not found response (HTTP 404).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_filename Default is errors/not_found.phtml
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function notFound($view_filename = 'errors/not_found.phtml', $variables = [])
    {
        return Response::fromCode(404, $view_filename, $variables);
    }

    /**
     * Create an internal server error response (HTTP 500).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_filename Default is errors/internal_server_error.phtml
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function internalServerError($view_filename = 'errors/internal_server_error.phtml', $variables = [])
    {
        return Response::fromCode(500, $view_filename, $variables);
    }

    /**
     * Create a Response from a HTTP status code.
     *
     * @param integer $code The HTTP code to set for the response
     * @param string $view_filename The name of a view file, under the views_path.
     * @param mixed[] $variables A list of optional variables to pass to the view
     *
     * @throws \Minz\Errors\ResponseError if the code is not a valid HTTP status code
     * @throws \Minz\Errors\ResponseError if the view file doesn't exist
     *
     * @return \Minz\Response
     */
    public static function fromCode($code, $view_filename, $variables = [])
    {
        $response = new Response();
        $response->setCode($code);
        $response->setHeader('Content-Type', 'text/html');
        $response->setViewFilename($view_filename);
        $response->setVariables($variables);
        return $response;
    }

    /** @var integer */
    private $code;

    /** @var string[] */
    private $headers = [];

    /** @var mixed[] */
    private $variables = [];

    /** @var string */
    private $view_filename;

    /**
     * @return string The current view filename
     */
    public function viewFilename()
    {
        return $this->view_filename;
    }

    /**
     * @param string $view_filename
     *
     * @throws \Minz\Errors\ResponseError if the view filename doesn't exist
     *
     * @return void
     */
    public function setViewFilename($view_filename)
    {
        $view_filepath = $this->viewFilepath($view_filename);
        if (!file_exists($view_filepath)) {
            $missing_file = Configuration::$views_path . '/' . $view_filename;
            throw new Errors\ResponseError("{$missing_file} file cannot be found.");
        }

        $this->view_filename = $view_filename;
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
     * @param mixed[] $variables
     *
     * @return void
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    /**
     * Generate and return the content from the view file.
     *
     * The view file is interpreted with access to the variables.
     *
     * @return string
     */
    public function render()
    {
        $view_filepath = $this->viewFilepath($this->view_filename);
        $view = new View($view_filepath);
        return $view->build($this->variables);
    }

    /**
     * Return the full path to a view file
     *
     * @param string $view_filename
     *
     * @return string
     */
    private function viewFilepath($view_filename)
    {
        $app_path = Configuration::$app_path;
        $views_path = Configuration::$views_path;
        return "{$app_path}/{$views_path}/{$view_filename}";
    }
}
