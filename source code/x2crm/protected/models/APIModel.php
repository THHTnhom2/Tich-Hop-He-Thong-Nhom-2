<?php
/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2015 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/
 
/**
 * Standalone model class for interaction with X2Engine's API
 *
 * Remote data insertion & lookup API model. Has multiple magic methods and
 * automatically makes cURL requests to API controller for ease of use. For each
 * kind of request, see the method in ApiController that corresponds to it. To
 * view this reference, look at the URL path for the method. For example 'api/create'
 * corresponds to actionCreate in ApiController.
 *
 * @package application.models
 * @author Jake Houser <jake@x2engine.com>, Demitri Morgan <demitri@x2engine.com>
 * @property mixed $responseObject Response data from the server
 * @property array $modelErrors Validation errors, if any, from the server.
 * @property int $responseCode (read-only) The most recent HTTP response code
 *	sent back from the server
 */
class APIModel {

    /**
     * The user to authenticate with.  Set in constructor.
     * @var string
     */
    private $_user = '';

	/**
	 * The response object from the server
	 * @var array
	 */
	private $_responseObject = null;

	/**
	 * Response code from the server
	 * @var int
	 */
	private $_responseCode = null;

    /**
     * The corresponding user key to authenticate with.  Set in constructor.
     * @var string
     */
    private $_userKey = '';

    /**
     * The base URL of the server for the API to connect to. (i.e. www.yourserver.com/x2engine)
     * @var string
     */
    private $_baseUrl = '';

	private $_modelErrors;

    /**
     * Attributes to be used for creating/updating models.
     * @var array
     */
    public $attributes;

    /**
     * Errors generated by API calls.
     * @var array
     */
    public $errors;

    /**
     * Constructs a new API model and sets private variables.
     * @param string $user The username to authenticate with
     * @param string $userKey The user key to authenticate with
     * @param string $baseUrl The base path of the server for the API to connect to (i.e. www.yourserver.com/x2engine)
     */
    public function __construct($user = null, $userKey = null, $baseUrl = null) {
        $this->_user = $user;
        $this->_userKey = $userKey;
        $this->_baseUrl = $baseUrl;
		if(strpos($baseUrl,'http://') !== 0) // Assume http if unspecified
			$this->_baseUrl = "http://{$baseUrl}";
		$lenUrl = strlen($baseUrl);
		if(strpos($baseUrl,'index.php') !== $lenUrl-9 && strpos($baseUrl,'index-test.php') !== $lenUrl-14) { // Assume using non-test index
			$this->_baseUrl = rtrim($this->_baseUrl,'/').'/index.php';
		}
    }

	/**
	 * Getter method for {@link modelErrors}
	 * @return type
	 */
	public function getModelErrors() {
		if(isset($this->_modelErrors))
			return $this->_modelErrors;
		else
			return array();
	}

	/**
	 * Setter for {@link responseObject}
	 * @param type $response
	 */
	public function setResponseObject($response) {
		if(is_string($response)) {
			$this->_responseObject = json_decode($response,1);
			if(is_null($this->_responseObject)) // Set it equal to the error returned
				$this->_responseObject = $response;
			else if (is_array($this->_responseObject)) {
				if(array_key_exists('modelErrors', $this->_responseObject) && !empty($this->_responseObject['error'])){
					$this->_modelErrors = $this->_responseObject['modelErrors'];
				}else{
					$this->_modelErrors = array();
				}
			} else {
				$this->_modelErrors = array();
			}

		} else if(is_array($response)) {
			$this->_responseObject = $response;
        }
	}

	/**
	 * Magic getter for {@link responseObject}
	 * @return type
	 */
	public function getResponseObject() {
		return $this->_responseObject;
	}

	/**
	 * Magic getter for {@link responseCode}
	 * @return type
	 */
	public function getResponseCode() {
		return $this->_responseCode;
	}

	/**
	 * Obtain the list of tags associated with the model
	 * @param type $modelName
	 * @param type $modelId
	 * @return type
	 */
	public function getTags($modelName,$modelId) {
		$ch = $this->_curlHandle("api/tags?".http_build_query(array(
					'model' => $modelName,
					'id' => $modelId
				),'','&'));
		return json_decode(curl_exec($ch),1);
	}

	/**
	 * Tag the model record
	 * @param type $modelName
	 * @param type $modelId
	 * @param type $tags
	 * @return type A
	 */
	public function addTags($modelName,$modelId,$tags){
		return json_encode($this->_send("api/tags/$modelName/$modelId", array(
			'tags' => json_encode(is_array($tags) ? $tags : array($tags))
		)),1);
	}

	/**
	 * Delete a tag from the model record
	 * @param type $modelName
	 * @param type $modelId
	 * @param type $tag
	 * @return type
	 */
	public function removeTag($modelName,$modelId,$tag) {
		$ch = $this->_curlHandle("api/tags/$modelName/$modelId/".ltrim($tag,'#'),array(),array(CURLOPT_CUSTOMREQUEST=>'DELETE'));
		return json_decode(curl_exec($ch),1);
	}


	/**
	 * Sets the model's attributes equal to those of the model contained in the
	 * response from the API, if any, and returns true or false based on how the
	 * API request returned (success or failure).
	 * @param bool $responseIsModel The response object is the attributes of the model
	 */
	public function processResponse($responseIsModel=false) {
		if(is_array($this->responseObject)){
			// Server responded with a valid JSON
			if(array_key_exists('modelErrors',$this->responseObject))
				// Populate model errors if any:
				$this->_modelErrors = $this->responseObject['modelErrors'];
			if(array_key_exists('model', $this->responseObject) && array_key_exists('error',$this->responseObject)){
				// API is responding using the data structure where the returned
				// model's attributes are stored in the "model" property of the
				// JSON object.
				if(!$this->responseObject['error'] && $this->responseCode == 200) {
					// No error. Update local attributes:
					$this->attributes = $this->responseObject['model'];
					return true;
				}else{
					// API responded with error=true due to validation error
					$this->errors = $this->responseObject['message'];
					return false;
				}
			}else if($responseIsModel && $this->responseCode == 200){
				// The action was using the older format where the returned JSON
				// *is* the model's attributes. Update local attributes:
				$this->attributes = $this->responseObject;
				return true;
			}else if($this->responseCode == 200){
				// API responded with error=false, but there's no "model" property.
				// Whatever happened, it succeeded, so do nothing else (nothing
				// else is necessary) and return true.
				return true;
			} else {
				// API responded with a valid JSON, but the request did not
				// succeed due to validation error, permissions/authentication
				// error, or some other error. Thus, simply return false.
				return false;
			}
		}else{
			// In this case, there's an unrecognized error message that the server
			// returned for whatever reason.
			$this->errors = $this->responseObject;
			return false;
		}
	}

	/**
	 * Creates or updates a model of a given type name using the current attributes.
	 * @param type $modelName
	 * @return boolean
	 */
	public function modelCreateUpdate($modelName,$action,$attributes=array()) {
        $ccUrl = "api/$action/model/$modelName";
		if($action=='update')
			$ccUrl .= '?'.http_build_query(array('id'=>$this->id),'','&');
        $this->responseObject = $this->_send($ccUrl, array_merge($this->attributes, $attributes));
		return $this->processResponse();
	}

	/**
	 * Generic find-by-attributes method
	 */
	public function modelLookup($modelName) {
		foreach($this->attributes as $key=>$value){
			// Exclude null attributes from lookup
            if(is_null($value) || $value==''){
                unset($this->attributes[$key]);
            }
        }
        $action = empty($this->attributes['id']) || count($this->attributes) > 1
            ? "api/lookup/model/$modelName"
            : "api/view/model/$modelName";
		$this->responseObject = $this->_send($action,$this->attributes);
		return $this->processResponse(true);
	}

    /**
     * Creates a contact with attributes specified in the APIModel's attributes property.
     * @param boolean $leadRouting Boolean whether or not to use lead routing rules for contact assigned to.
     * @return string Response code from API request.
     */
    public function contactCreate($leadRouting = true) {
        $attributes = array(
            'assignedTo' => $this->_user,
            'visibility' => '1',
        );
        if ($leadRouting) {
            $attributes['_leadRouting'] = 1;
        }
	    return $this->modelCreateUpdate('Contacts','create',$attributes);
    }

    /**
     * Updates a contact with the specified attributes.
     * @param int $id Optional ID of the contact, will be used if the id attribute is not set.
     * @return string Response code from the API request.
     */
    public function contactUpdate($id = null) {
        if (!isset($this->id))
            $this->id = $id;
        $this->modelCreateUpdate('Contacts','update');
    }

    /**
     * Looks up a contact with the attributes set on the model.
     * @return string Response code from the API request.  JSON string of attributes on success.
     */
    public function contactLookup() {
		return $this->modelLookup('Contacts');
    }

    /**
     * Deletes a contact with the specified ID.
     * @param int $id Optional ID of the contact, will be used if id attribute is not set.
     * @return string Response code of the API request.
     */
    public function contactDelete($id = null) {
        if (!isset($this->id))
            $this->id = $id;
        $this->responseObject = $this->_send('api/delete/model/Contacts', $this->attributes);
        return $this->processResponse();
    }

    /**
     * Clears the attributes set on the model.
     */
    public function clearAttributes() {
        $this->attributes = array();
    }

    /**
     *
     * @param type $action
     * @return type
     */
    public function checkAccess($action){
        $result=$this->_send('api/checkPermissions/action/'.$action.'/username/'.$this->_user.'/api/1',array());
        return $result=='true';
    }

	/**
	 * Creates a new cURL resource handle with user authentication parameters.
	 *
	 * @param type $url
	 * @param type $postData
	 * @param type $curlOpts
	 * @return resource
	 */
	private function _curlHandle($url,$postData=array(),$curlOpts=array()) {
		$post = !empty($postData);
		// Authentication parameters
		$authOpts = array(
				'userKey' => $this->_userKey,
				'user' => $this->_user
		);
		if(!$post) {
			// The authentication parameters will need to go into the URL, since
			// this won't be a POST request.
			//
			// if "?" is there already, concatenate with "&". Otherwise, "?"
			$appendParams = strpos($url,'?') !== false; // use "&" to concatenates
			$url .= ($appendParams ? '&' : '?').http_build_query($authOpts,'','&');
		}
		// Curl handle
		$ch = curl_init($this->_baseUrl.'/'.$url);
		// Set default options for the curl resource:
		curl_setopt_array($ch,array(
			// Tell CURL to receive response data (and don't return null) even if
			// the server returned with an error, so that we can have the response
			// data and get the response code with curl_getinfo:
			CURLOPT_HTTP200ALIASES => array(400,401,403,404,413,500,501),
			// Make it a POST request (or not):
			CURLOPT_POST => $post,
			// Response capture necessary:
			CURLOPT_RETURNTRANSFER => 1,
		));
		// Set custom options next so that they override defaults:
		curl_setopt_array($ch,$curlOpts);
		if($post) // Set payload data
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postData = array_merge($postData,$authOpts));
		return $ch;
	}

    /**
     * Function that sends a post request to the server.
	 *
     * @param string $url The full request URL including base path and route for create, update etc.
     * @param mixed $postData Post data to be included with the request.
     * @return string Response code sent by API controller.
     */
    private function _send($url, $postData){
        $ccSession = $this->_curlHandle($url,$postData);
        curl_setopt($ccSession, CURLOPT_RETURNTRANSFER, 1);
        $ccResult = curl_exec($ccSession);
		$this->_responseCode = curl_getinfo($ccSession,CURLINFO_HTTP_CODE);
        return $ccResult;
    }

    /**
     * Magic method that handles setting attributes of the model.
     * @param string $name Attribute name.
     * @param string $value Attribute value.
     */
    public function __set($name, $value) {
		$setter = 'set'.ucfirst($name);
        if (strpos($name, '_') === 0 || $name == 'attributes') {
			// Set the value directly
            $this->$name = $value;
        } else if(method_exists($this,$setter)) {
			// Call the magic setter
			$this->$setter($value);
		} else {
			// Set the named attribute
            $this->attributes[$name] = $value;
        }
    }

    /**
     * Magic method that handles getting of an attribute of the model.
     * @param string $name The name of the attribute.
     * @return The value of the attribute if set, else null .
     */
    public function __get($name) {
		$getter = 'get'.ucfirst($name);
        if (strpos($name, '_') === 0 || $name == 'attributes') {
			// Return the named property
            return $this->$name;
        } else if (method_exists($this,$getter)) {
			// Return whatever the magic getter returns
			return $this->$getter();
		} else if (isset($this->attributes[$name])) {
			// return the named attribute
            return $this->attributes[$name];
        }
        return null;
    }

    /**
     * Magic method to check if an attribute is set.
     * @param type $name Name of the attribute
     * @return boolean Whether or not the attribute is set.
     */
    public function __isset($name) {
        if (strpos($name, '_') === 0 || $name == 'attributes') {
            return isset($this->$name);
        } else {
            return isset($this->attributes[$name]);
        }
    }

}

?>
