<?php
/**
 * Provides Verification behavior for a Model.
 *
 * Required settings for this behavior:
 *
 * 'expiresAfter' => The duration before expiration in hours.
 * 'fields' => array(
 *     'key'       => The column name for the verification key,
 *     'code'      => The column name for the verification code,
 *     'timestamp' => The column name for the verification timestamp
 * )
 *
 * @author https://github.com/bretten
 */
App::uses('ModelBehavior', 'Model');

class VerificationBehavior extends ModelBehavior {

    /**
     * Default settings
     *
     * @var array
     */
    public $settings = array(
        'expiresAfter' => 48,
        'fields' => array(
            'key' => 'verify_key',
            'code' => 'verify_code',
            'timestamp' => 'verify_time'
        )
    );

    /**
     * Initiate behavior
     *
     * @param Model $Model instance of model
     * @param array $config array of configuration settings.
     * @return void
     */
    public function setup(Model $Model, $config = array()) {
        $this->settings = array_merge($this->settings, $config);
    }

    /**
     * Generates verification data for a Model.
     *
     * The verification key and code are two strings that can be used to verify a user is valid.
     * The verification timestamp can be used to check if the verification data has expired.
     *
     * @param Model $Model Model using this behavior
     * @param int $id The ID of the Model to generate verification data for
     * @return mixed On success Model::$data if its not empty or true, false on failure
     */
    public function generateVerification(Model $Model, $id = null) {
        if ($id) {
            $Model->id = $id;
        }

        // No ID, so cannot save the verification data
        if (!$Model->getID()) {
            return false;
        }

        // Generate verification data
        $data = array(
            $Model->alias => array(
                $Model->primaryKey => $id,
                $this->settings['fields']['key'] => Security::generateAuthKey(),
                $this->settings['fields']['code'] => uniqid(),
                $this->settings['fields']['timestamp'] => date("Y-m-d H:i:s"),
                'modified' => false
            )
        );

        return $Model->save($data, false, array(
            $Model->primaryKey,
            $this->settings['fields']['key'],
            $this->settings['fields']['code'],
            $this->settings['fields']['timestamp']
        ));
    }

    /**
     * Given a verification key and code, verifies against the Model stored in the database.
     *
     * Checks that the verification code matches and that it is before the expiration time.
     *
     * @param Model $Model Model using this behavior
     * @param string $key The verification key
     * @param string $code The verification code
     * @return mixed On success, the verified Model, false on failure
     */
    public function verify(Model $Model, $key, $code) {
        // Find the Model associated with the key
        $actual = $Model->find('first', array(
            'conditions' => array(
                $Model->alias . "." . $this->settings['fields']['key'] => $key
            ),
            'fields' => array(
                $Model->alias . "." . $Model->primaryKey,
                $Model->alias . "." . $this->settings['fields']['code'],
                $Model->alias . "." . $this->settings['fields']['timestamp']
            )
        ));

        // Check that the verification code matches and that the current time is before the expiration time
        if ($actual && $actual[$Model->alias][$this->settings['fields']['code']] == $code &&
            $this->beforeExpiration($actual[$Model->alias][$this->settings['fields']['timestamp']])
        ) {
            return $actual;
        }

        return false;
    }

    /**
     * Checks that the current time is before the specified time + the expiresAfter setting.
     *
     * @param mixed $time Datetime string or UNIX timestamp
     * @return bool
     */
    protected function beforeExpiration($time) {
        // Convert time strings to a UNIX timestamp
        if (!is_numeric($time) || (int)$time == $time) {
            $time = strtotime($time);
        }

        // Calculate the expiration time
        $expiration = strtotime('+' . $this->settings['expiresAfter'] . ' hours', $time);

        // The current time is before the expiration time
        if (time() <= $expiration) {
            return true;
        }

        return false;
    }
}
