<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

use Exception;

/**
 * YourlsProxy
 *
 * Forwards a URL for shortening to YOURLS (your own URL shortener) and stores
 * the result.
 */
class YourlsProxy
{
    /**
     * error message
     *
     * @access private
     * @var    string
     */
    private $_error = '';

    /**
     * shortened URL
     *
     * @access private
     * @var    string
     */
    private $_url = '';

    /**
     * constructor
     *
     * initializes and runs PrivateBin
     *
     * @access public
     * @param string $link
     */
public function __construct(Configuration $conf, $link)
{
    $api_url = getenv('CHHOTO_API_URL');
    $api_key = getenv('CHHOTO_API_KEY');

    // Prepare the JSON payload
    $payload = json_encode([
        'longlink' => $link,
        'shortlink' => ''
    ]);

    // Set up the stream context with JSON content type and API key header
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-API-Key: ' . $api_key
            ],
            'content' => $payload
        ]
    ]);

    try {
        $data = file_get_contents($api_url . '/api/new', false, $context);
        $response = Json::decode($data);

        if (!is_null($response) && isset($response['success']) && $response['success'] === true && isset($response['shorturl'])) {
            $this->_url = $response['shorturl'];
        } else {
            $this->_error = isset($response['error']) ? $response['error'] : 'Error parsing API response.';
            error_log('API response: ' . $data);
        }
    } catch (Exception $e) {
        $this->_error = 'Error calling API service.';
        error_log('API call error: ' . $e->getMessage());
        return;
    }
}

    /**
     * Returns the (untranslated) error message
     *
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns the shortened URL
     *
     * @access public
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Returns true if any error has occurred
     *
     * @access public
     * @return bool
     */
    public function isError()
    {
        return !empty($this->_error);
    }
}
