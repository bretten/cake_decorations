<?php
/**
 * Provides common User functionality.
 *
 * @author https://github.com/bretten
 */
App::uses('ModelBehavior', 'Model');

class UserBehavior extends ModelBehavior {

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
     * Before validate method.
     *
     * @param Model $Model Model using this behavior
     * @param array $options Options passed from Model::save().
     * @return mixed False or null will abort the operation. Any other result will continue.
     */
    public function beforeValidate(Model $Model, $options = array()) {
        // Adds the validation rule for confirming the passwords match
        if (isset($options['confirmPassword']) && $options['confirmPassword'] === true) {
            $Model->validator()->add($this->settings['confirmPassword']['field'], array(
                $this->settings['confirmPassword']['ruleName'] => array(
                    'rule' => array('confirmPassword'),
                    'message' => $this->settings['confirmPassword']['ruleMessage'],
                    'required' => true
                )
            ));
        }
        return true;
    }

    /**
     * Before save method.
     *
     * @param Model $Model Model instance
     * @param array $options Options passed from Model::save().
     * @return boolean true to continue, false to abort the save
     */
    public function beforeSave(Model $Model, $options = array()) {
        // Hash the password field before saving
        if (isset($Model->data[$Model->alias][$this->settings['passwordField']])) {
            $Model->data[$Model->alias][$this->settings['passwordField']] =
                AuthComponent::password($Model->data[$Model->alias][$this->settings['passwordField']]);
        }
        return true;
    }

    /**
     * Validation method for confirming a User's password.
     *
     * @param Model $Model Model instance
     * @param $check
     * @return bool
     */
    public function confirmPassword(Model $Model, $check) {
        if ($Model->data[$Model->alias][$this->settings['passwordField']] ==
            $check[$this->settings['confirmPassword']['field']]
        ) {
            return true;
        }
        return false;
    }

}