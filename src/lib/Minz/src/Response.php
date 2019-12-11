<?php

namespace Minz;

/**
 * The Response represents the answer given to a Request, and returned to the
 * user.
 *
 * A view pointer is attached to a Response. This pointer points to a file
 * generating the content which is returned to the user. It is generally a
 * `.phtml` file.
 *
 * A view pointer is in the form of `controller_name#filename`. For instance,
 * `rabbits#items.phtml` targets the file `src/rabbits/views/items.phtml`.
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

    /** @var string[] */
    public const EXTENSION_TO_CONTENT_TYPE = [
        'html' => 'text/html',
        'json' => 'application/json',
        'phtml' => 'text/html',
        'txt' => 'text/plain',
        'xml' => 'text/xml',
    ];

    /**
     * Create a successful response (HTTP 200).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function ok($view_pointer = '', $variables = [])
    {
        return Response::fromCode(200, $view_pointer, $variables);
    }

    /**
     * Create an accepted response (HTTP 202).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function accepted($view_pointer = '', $variables = [])
    {
        return Response::fromCode(202, $view_pointer, $variables);
    }

    /**
     * Create a bad request response (HTTP 400).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function badRequest($view_pointer = '', $variables = [])
    {
        return Response::fromCode(400, $view_pointer, $variables);
    }

    /**
     * Create a not found response (HTTP 404).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function notFound($view_pointer = '', $variables = [])
    {
        return Response::fromCode(404, $view_pointer, $variables);
    }

    /**
     * Create an internal server error response (HTTP 500).
     *
     * @see \Minz\Response::fromCode()
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\ResponseError
     *
     * @return \Minz\Response
     */
    public static function internalServerError($view_pointer = '', $variables = [])
    {
        return Response::fromCode(500, $view_pointer, $variables);
    }

    /**
     * Create a Response from a HTTP status code.
     *
     * @param integer $code The HTTP code to set for the response
     * @param string $view_pointer The view pointer to set to the response
     * @param mixed[] $variables A list of optional variables to pass to the view
     *
     * @throws \Minz\Errors\ResponseError if the code is not a valid HTTP status code
     * @throws \Minz\Errors\ResponseError if the view pointer file doesn't exist
     * @throws \Minz\Errors\ResponseError if the view pointer file extension is
     *                                    not supported
     *
     * @return \Minz\Response
     */
    public static function fromCode($code, $view_pointer, $variables = [])
    {
        $response = new Response();
        $response->setCode($code);
        $response->setViewPointer($view_pointer);
        $response->setVariables($variables);
        if ($view_pointer) {
            $content_type = self::contentTypeFromViewPointer($view_pointer);
        } else {
            $content_type = 'text/plain';
        }
        $response->setHeader('Content-Type', $content_type);
        return $response;
    }

    /** @var integer */
    private $code;

    /** @var string[] */
    private $headers = [];

    /** @var mixed[] */
    private $variables = [];

    /** @var string */
    private $view_pointer;

    /**
     * @return string The current view pointer
     */
    public function viewPointer()
    {
        return $this->view_pointer;
    }

    /**
     * @param string $view_pointer A pointer to a view file (can be empty)
     *
     * @throws \Minz\Errors\ResponseError if the view pointer doesn't contain a hash
     * @throws \Minz\Errors\ResponseError if the view pointer file doesn't exist
     *
     * @return void
     */
    public function setViewPointer($view_pointer)
    {
        if ($view_pointer !== '') {
            if (strpos($view_pointer, '#') === false) {
                throw new Errors\ResponseError(
                    "{$view_pointer} view pointer must contain a hash (#)."
                );
            }

            $view_filepath = self::viewFilepath($view_pointer);
            if (!file_exists($view_filepath)) {
                list($controller_name, $view_filename) = explode('#', $view_pointer);
                $missing_file = "src/{$controller_name}/views/{$view_filename}";
                throw new Errors\ResponseError("{$missing_file} file cannot be found.");
            }
        }

        $this->view_pointer = $view_pointer;
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
     * Generate and return the content from the view pointer file.
     *
     * The view pointer file is interpreted with access to the variables.
     *
     * @return string
     */
    public function render()
    {
        if ($this->view_pointer) {
            $view_filepath = self::viewFilepath($this->view_pointer);
            $view = new View($view_filepath);
            return $view->build($this->variables);
        } else {
            return '';
        }
    }

    /**
     * Return the full path to a view pointer file
     *
     * @param string $view_pointer
     *
     * @return string
     */
    private static function viewFilepath($view_pointer)
    {
        list($controller_name, $view_filename) = explode('#', $view_pointer);
        $app_path = Configuration::$app_path;
        return "{$app_path}/src/{$controller_name}/views/{$view_filename}";
    }

    /**
     * Return the content type associated to a view pointer file extension
     *
     * @param string $view_pointer
     *
     * @throws \Minz\Errors\ResponseError if the view pointer file extension is
     *                                    not supported
     *
     * @return string
     */
    private static function contentTypeFromViewPointer($view_pointer)
    {
        $file_extension = pathinfo($view_pointer, PATHINFO_EXTENSION);
        if (!isset(self::EXTENSION_TO_CONTENT_TYPE[$file_extension])) {
            throw new Errors\ResponseError(
                "{$file_extension} is not a supported view file extension."
            );
        }
        return self::EXTENSION_TO_CONTENT_TYPE[$file_extension];
    }
}
