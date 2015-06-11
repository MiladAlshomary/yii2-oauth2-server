<?php
/**
 * @link https://github.com/borodulin/yii2-oauth-server
 * @copyright Copyright (c) 2015 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-oauth-server/blob/master/LICENSE
 */

namespace conquer\oauth2\models;

use Yii;

/**
 * This is the model class for table "oauth_authorization_code".
 *
 * @property string $authorization_code
 * @property string $client_id
 * @property integer $user_id
 * @property string $redirect_uri
 * @property integer $expires
 * @property string $scopes
 * @property string $id_token
 *
 * @property OauthClient $client
 * @property User $user
 */
class OauthAuthorizationCode extends \yii\db\ActiveRecord
{
    /**
     * 
     * @var OauthClient
     */
    private $_client;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%oauth_authorization_code}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['authorization_code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'id_token'], 'required'],
            [['user_id', 'expires'], 'integer'],
            [['scopes'], 'string'],
            [['authorization_code'], 'string', 'max' => 40],
            [['client_id', 'id_token'], 'string', 'max' => 80],
            [['redirect_uri'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'authorization_code' => 'Authorization Code',
            'client_id' => 'Client ID',
            'user_id' => 'User ID',
            'redirect_uri' => 'Redirect Uri',
            'expires' => 'Expires',
            'scopes' => 'Scopes',
            'id_token' => 'Id Token',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(OauthClient::className(), ['client_id' => 'client_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['user_id' => 'user_id']);
    }
    
    public function validateClientId()
    {
        if(is_null($this->_client))
            $this->_client = OauthClient::findOne($this->client_id);
    }
    
    /**
     * 
     * @throws OauthException
     * @return \conquer\oauth\models\OauthAuthorizationCode
     */
    public static function validateTokenRequest()
    {
        $request = \Yii::$app->request;
        if (!$code = $request->post('code'))
            throw new OauthException('Missing parameter: "code" is required');
            
        /* @var $authCode \conquer\oauth\models\OauthAuthorizationCode */
        if (!$authCode = static::findOne(['authorization_code'=>$code])) 
            throw new OauthException('Authorization code doesn\'t exist or is invalid for the client', 'invalid_grant');

        /**
         * @link http://tools.ietf.org/html/rfc6749#section-4.1.3
         */
        if (strcmp($authCode->redirect_uri, $request->post('redirect_uri'))!==0)
            throw new OauthException("The redirect URI is missing or do not match", 'redirect_uri_mismatch');

        if (empty($authCode->expires)||($authCode->expires < time()))
            throw new OauthException("The authorization code has expired", 'invalid_grant');

        return $authCode;
    }
    
}