<?php
namespace App\Core;

/**
 * Class Controller
 *
 * Provides a simple base controller with a render method. The render
 * method passes data to a view and includes a master layout. Views are
 * located in the resources/views directory. The master layout is
 * responsible for including the selected view based on the $viewFile
 * variable. Each controller should extend this class and call
 * $this->render('view-name', $data) to display output.
 */
class Controller
{
    /**
     * Render a view within the main layout.
     *
     * @param string $view Name of the view file (without .php extension)
     * @param array  $data Associative array of data to extract as
     *                     variables within the view
     */
    protected function render(string $view, array $data = []): void
    {
        // Extract the provided data so variables become available to the view
        extract($data);

        // Determine path to the view file
        $viewFile = __DIR__ . '/../../resources/views/' . $view . '.php';

        // Include the layout. The layout will include $viewFile at the
        // appropriate location. If the view file does not exist, a
        // default message will be displayed.
        include __DIR__ . '/../../resources/views/layout.php';
    }
}