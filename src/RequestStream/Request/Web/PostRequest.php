<?php

/**
 * This file is part of the RequestStream package
 *
 * (c) Vitaliy Zhuk <zhuk2205@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace RequestStream\Request\Web;

/**
 * Post request
 */
class PostRequest extends DefaultRequest
{
    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * @var PostDataBag
     */
    protected $postData;

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->postData = new PostDataBag;
    }

    /**
     * Set post data
     *
     * @param PostDataBag $postData
     */
    public function setPostData(PostDataBag $postData)
    {
        $this->postData = $postData;

        return $this;
    }

    /**
     * Get post data
     *
     * @return PostDataBag
     */
    public function getPostData()
    {
        return $this->postData;
    }

    /**
     * {@inheritDoc}
     */
    public function setMethod($method)
    {
        throw new \RuntimeException(sprintf(
            'Can\'t set HTTP method ("%s") to POST Request.',
            $method
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function prepare()
    {
        parent::prepare();
        $this->headers['Content-Type'] = 'multipart/form-data; boundary="' . $this->postData->generateBundary() . '"';
        $this->headers['Content-Length'] = $this->postData->getContentLength();
    }

    /**
     * __toString
     */
    public function __toString()
    {
        return rtrim(parent::__toString()) .
            "\r\n" . ((string) $this->postData) . "\r\n\r\n";
    }
}