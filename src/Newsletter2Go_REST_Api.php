<?php

/**
 * For API Documentation see https://docs.newsletter2go.com
 */
class Newsletter2Go_REST_Api
{

    const GRANT_TYPE = "https://nl2go.com/jwt";
    const BASE_URL = "https://api.newsletter2go.com";

    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PATCH = "PATCH";
    const METHOD_DELETE = "DELETE";

    private $__userEmail = "email";

    private $__userPw = "password";

    private $__userAuthKey = "authkey";

    private $__accessToken = "";

    private $__refreshToken = "";

    private $__sslVerification = true;

    /**
     * Newsletter2Go_REST_Api constructor.
     *
     * @param string $authKey      auth key
     * @param string $userEmail    email
     * @param string $userPassword password
     */
    public function __construct($authKey, $userEmail, $userPassword)
    {
        $this->__userAuthKey = $authKey;
        $this->__userEmail = $userEmail;
        $this->__userPw = $userPassword;
    }

    /**
     * Auth
     *
     * @return void
     */
    public function auth()
    {
        $this->__getToken();
    }

    /**
     * Set SSL verification
     *
     * @param bool $enable enable
     * @return void
     */
    public function setSSLVerification($enable)
    {
        $this->__sslVerification = $enable;
    }

    /**
     * Get auth token
     *
     * @return void
     */
    private function __getToken()
    {
        $endpoint = "/oauth/v2/token";
        $data = array(
            "username" => $this->__userEmail,
            "password" => $this->__userPw,
            "grant_type" => static::GRANT_TYPE

        );
        $response = $this->__curl('Basic ' . base64_encode($this->__userAuthKey),
            $endpoint, $data, "POST");
        $this->__accessToken = $response->access_token;
        $this->__refreshToken = $response->refresh_token;
    }

    /**
     * get all users in this account
     *
     * @return stdClass
     */
    public function getUsers()
    {
        $endpoint = "/users";
        $data = array(
            "_expand" => true
        );

        return $this->curl($endpoint, $data);
    }

    /**
     * get all newsletters in a list
     *
     * @param string $listId list Id
     * @return stdClass
     */
    public function getNewsletters($listId)
    {
        $endpoint = "/lists/$listId/newsletters";
        $data = array(
            "_expand" => true
        );

        return $this->curl($endpoint, $data);
    }

    /**
     * get all templates in a list
     *
     * @param string $listId list Id
     * @return stdClass
     */
    public function getTemplates($listId)
    {
        $endpoint = "/lists/$listId/templates";
        $data = array(
            "_expand" => true,
            "_filter" => "type=='custom',type=='premium'"
        );

        return $this->curl($endpoint, $data);
    }

    /**
     * Get a single template templates in a list
     *
     * @param string $listId     list Id
     * @param string $templateId template Id
     * @return stdClass
     */
    public function getTemplate($listId, $templateId)
    {
        $endpoint = "/lists/$listId/templates/$templateId";
        $data = array(
            "_expand" => true,
        );

        return $this->curl($endpoint, $data);
    }

    /**
     * create a new newsletter
     * https://docs.newsletter2go.com/#!/Newsletter/createNewsletter
     *
     * @param string $listId     listId
     * @param string $type       can be 'default','transaction','doi'
     * @param string $name       the name of the newsletter
     * @param string $headerFrom the from e-mail address
     * @param string $subject    the subject of the newsletter
     * @param string $html       you can pass html directly
     * @param string $json       or you can pass json, which you can get from an existing template/mailing
     * @throws \Exception
     * @return stdClass
     */
    public function createNewsletter($listId, $type, $name, $headerFrom, $subject, $html = null, $json = null)
    {
        if (!in_array($type, array("transaction", "default", "doi"))) {
            throw new \Exception("Mailing type not supported");
        }
        $endpoint = "/lists/$listId/newsletters";
        $data = array(
            "type" => $type,
            "name" => $name,
            "subject" => $subject,
            "header_from_email" => $headerFrom
        );
        if (isset($html)) {
            $data['html'] = $html;
        }
        if (isset($json)) {
            $data['json'] = $json;
        }

        return $this->curl($endpoint, $data, static::METHOD_POST);
    }

    /**
     * Update the HTML of an existing newsletter
     * https://docs.newsletter2go.com/#!/Newsletter/updateNewsletter
     *
     * @param string $newsletterId newsletter Id
     * @param string $html         html
     * @return stdClass
     */
    public function updateHTML($newsletterId, $html = null)
    {
        $endpoint = "/newsletters/$newsletterId";
        $data = array(
            "html" => $html
        );

        return $this->curl($endpoint, $data, static::METHOD_PATCH);
    }

    /**
     * Update subject of an existing newsletter
     * https://docs.newsletter2go.com/#!/Newsletter/updateNewsletter
     *
     * @param string $newsletterId newsletter Id
     * @param string $subject      subject
     * @return stdClass
     */
    public function updateSubject($newsletterId, $subject = null)
    {
        $endpoint = "/newsletters/$newsletterId";
        $data = array(
            "subject" => $subject
        );

        return $this->curl($endpoint, $data, static::METHOD_PATCH);
    }

    /**
     * Update name, subject or html of an existing newsletter
     * https://docs.newsletter2go.com/#!/Newsletter/updateNewsletter
     *
     * @param string $newsletterId newsletter Id
     * @param string $name         name
     * @param string $subject      subject
     * @param string $html         html
     * @return stdClass
     */
    public function updateNewsletter($newsletterId, $name, $subject, $html)
    {
        $endpoint = "/newsletters/$newsletterId";

        return $this->curl($endpoint, array("name" => $name, "subject" => $subject, "html" => $html),
            static::METHOD_PATCH);
    }

    /**
     * delete a newsletter
     *
     * @param string $newsletterId newsletter Id
     * @return stdClass
     */
    public function deleteNewsletter($newsletterId)
    {
        $endpoint = "/newsletters/$newsletterId";

        return $this->curl($endpoint, array(), static::METHOD_DELETE);
    }

    /**
     * get all lists
     * https://docs.newsletter2go.com/#!/List/getLists
     *
     * @return stdClass
     */
    public function getLists()
    {
        $endpoint = "/lists";
        $data = array(
            "_expand" => true
        );

        return $this->curl($endpoint, $data);
    }

    /**
     * create new recipient
     * https://docs.newsletter2go.com/#!/Recipient/createRecipient
     *
     * @param string $listId    the id of the list where the recipient should be added to
     * @param string $email     string the email of the recipient
     * @param string $firstName the first name of the recipient
     * @param string $lastName  the alst name of the recipient
     * @param string $reason    reason for creating new recipient
     * @return stdClass
     */
    public function createRecipient($listId, $email, $firstName, $lastName, $reason)
    {
        $endpoint = "/recipients";
        $data = array(
            "list_id" => $listId,
            "email" => $email,
            "first_name" => $firstName,
            "last_name" => $lastName,
            "Vermerk" => $reason
        );

        return $this->curl($endpoint, $data, static::METHOD_POST);
    }

    /**
     * If you want to send transactional newsletters, you have to activate it first
     * https://docs.newsletter2go.com/#!/Newsletter/updateNewsletter
     *
     * @param string $newsletterId newsletter Id
     * @param string $state        can be 'active' or 'inactive'
     * @return stdClass
     */
    public function setTransactionalState($newsletterId, $state)
    {
        $endpoint = "/newsletters/$newsletterId";

        return $this->curl($endpoint, array("state" => $state), static::METHOD_PATCH);
    }

    /**
     * Send a newsletter
     * https://docs.newsletter2go.com/#!/Newsletter/sendNewsletter
     *
     * @param string $newsletterId  defines the newsletter that should be sent
     * @param array  $recipientData data of recipients
     * @return stdClass
     */
    public function sendNewsletter($newsletterId, $recipientData)
    {
        $endpoint = "/newsletters/$newsletterId/send";

        return $this->curl($endpoint, $recipientData, static::METHOD_POST);
    }

    /**
     * Get token and call __curl
     *
     * @param string $endpoint the endpoint to call (see docs.newsletter2go.com)
     * @param array  $data     the data to submit. In case of POST and PATCH its submitted as the body of the
     *                         request. In case of GET and PATCH it is used as GET-Params. See
     *                         docs.newsletter2go.com for supported parameters.
     * @param string $type     GET,PATCH,POST,DELETE
     * @return \stdClass
     * @throws \Exception
     */
    public function curl($endpoint, $data, $type = "GET")
    {
        if (!isset($this->__accessToken) || strlen($this->__accessToken) == 0) {
            $this->__getToken();
        }
        if (!isset($this->__accessToken) || strlen($this->__accessToken) == 0) {
            throw new \Exception("Authentication failed");
        }

        return $this->__curl('Bearer ' . $this->__accessToken, $endpoint, $data, $type);
    }

    /**
     * Curl
     *
     * @param string $authorization authorization
     * @param string $endpoint      endpoint
     * @param array  $data          data
     * @param string $type          type
     * @return mixed
     * @throws Exception
     */
    private function __curl($authorization, $endpoint, $data, $type = "GET")
    {
        $ch = curl_init();
        $dataString = json_encode($data);
        $getParams = "";
        if ($type == static::METHOD_POST || $type == static::METHOD_PATCH) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            $post = true;
        } else {
            if ($type == static::METHOD_GET || $type == static::METHOD_DELETE) {
                $post = false;
                $getParams = "?" . http_build_query($data);
            } else {
                throw new \Exception("Invalid HTTP method: " . $type);
            }
        }
        curl_setopt($ch, CURLOPT_POST, $post);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_URL, static::BASE_URL . $endpoint . $getParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $authorization,
            'Content-Length: ' . ($type == static::METHOD_GET || $type == static::METHOD_DELETE) ? 0 : strlen($dataString)
        ));
        if (!$this->__sslVerification) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}
