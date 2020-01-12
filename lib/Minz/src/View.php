<?php

namespace Minz;

/**
 * A View represents the (string) content to deliver to users.
 *
 * It is represented by a file under src/views. The view file is called
 * "pointer".
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class View
{
    /** @var string[] */
    public const EXTENSION_TO_CONTENT_TYPE = [
        'html' => 'text/html',
        'json' => 'application/json',
        'phtml' => 'text/html',
        'txt' => 'text/plain',
        'xml' => 'text/xml',
    ];

    /** @var string */
    private $filepath;

    /** @var string */
    private $content_type;

    /** @var string */
    private $pointer;

    /** @var mixed[] */
    private $variables;

    /** @var string|null */
    private $template_name;

    /** @var mixed[] */
    private $template_variables;

    /**
     * @param string $pointer
     * @param mixed[] $variables (optional)
     */
    public function __construct($pointer, $variables = [])
    {
        $this->setContentType($pointer);
        $this->setFilepath($pointer);
        $this->pointer = $pointer;
        $this->variables = $variables;
    }

    /**
     * @return string
     */
    public function pointer()
    {
        return $this->pointer;
    }

    /**
     * @return string
     */
    public function filepath()
    {
        return $this->filepath;
    }

    /**
     * @param string $pointer
     *
     * @throws \Minz\Errors\ViewError if the pointed file doesn't exist
     */
    public function setFilepath($pointer)
    {
        $app_path = Configuration::$app_path;
        $filepath = "{$app_path}/src/views/{$pointer}";
        if (!file_exists($filepath)) {
            $missing_file = "src/views/{$pointer}";
            throw new Errors\ViewError("{$missing_file} file cannot be found.");
        }

        $this->filepath = $filepath;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return $this->content_type;
    }

    /**
     * @param string $pointer
     *
     * @throws \Minz\Errors\ViewError if the pointed file extension is not
     *                                associated to a supported one
     */
    public function setContentType($pointer)
    {
        $file_extension = pathinfo($pointer, PATHINFO_EXTENSION);
        if (!isset(self::EXTENSION_TO_CONTENT_TYPE[$file_extension])) {
            throw new Errors\ViewError(
                "{$file_extension} is not a supported view file extension."
            );
        }
        $this->content_type = self::EXTENSION_TO_CONTENT_TYPE[$file_extension];
    }

    /**
     * Generate and return the content.
     *
     * Variables are passed and accessible in the view file.
     *
     * @return string The content generated by the view file
     */
    public function render()
    {
        foreach ($this->variables as $var_name => $var_value) {
            $$var_name = $var_value;
        }

        ob_start();
        include $this->filepath;
        $output = ob_get_clean();

        if ($this->template_name) {
            $template_filepath = self::templateFilepath($this->template_name);
            $this->template_variables['content'] = $output;
            $view = new View($template_filepath, $this->template_variables);
            $output = $view->render();
        }

        return $output;
    }

    /**
     * Allow to set a template to the view.
     *
     * It must be called from within the view file directly.
     *
     * @param string $template_name The name of the file under src/templates/
     * @param mixed[] $template_variables A list of variables to pass to the template
     *
     * @throws \Minz\Errors\ViewError if the template file doesn't exist
     * @throws \Minz\Errors\ViewError if the template variables aren't an array
     */
    private function template($template_name, $template_variables = [])
    {
        $template_filepath = self::templateFilepath($template_name);
        if (!file_exists($template_filepath)) {
            throw new Errors\ViewError(
                "{$template_name} template file does not exist."
            );
        }

        if (!is_array($template_variables)) {
            throw new Errors\ViewError(
                "Template variables parameter must be an array."
            );
        }

        $this->template_name = $template_name;
        $this->template_variables = $template_variables;
    }

    /**
     * Helper to find the path to a template file
     *
     * @param string $template_name
     *
     * @return string
     */
    private static function templateFilepath($template_name)
    {
        $app_path = Configuration::$app_path;
        return "{$app_path}/src/views/templates/{$template_name}";
    }
}
