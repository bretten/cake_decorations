<?php
/**
 * Represents a Model belonging to a User.
 *
 * Provides common functions for a Model that belongs to a User.
 *
 * @author https://github.com/bretten
 */
App::uses('ModelBehavior', 'Model');

class BelongsBehavior extends ModelBehavior {

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
 * Before save method.
 *
 * @param Model $Model Model instance
 * @param array $options Options passed from Model::save().
 * @return boolean true to continue, false to abort the save
 */
    public function beforeSave(Model $Model, $options = array()) {
        // Assures that the foreign key to the User table is set with the user id
        if (isset($options['associateOwner']) && $options['associateOwner'] === true) {
            if (isset($Model->data[$Model->alias])) {
                $Model->data[$Model->alias][$this->settings['foreignKey']] = AuthComponent::user($this->settings['userPrimaryKey']);
            } else {
                $Model->data[$this->settings['foreignKey']] = AuthComponent::user($this->settings['userPrimaryKey']);
            }
        }
    }

/**
 * Checks to see if the current Model belongs to the User.
 *
 * @param Model $Model Model instance
 * @param int $userId The id of the user.
 * @param int $id The id of the current Model.  If null, will check if an id is set.
 * @return boolean True indicates it belongs to the user
 */
    public function ownedBy(Model $Model, $userId, $id = null) {
        if ($id === null) {
            $id = $Model->getID();
        }
        
        if ($id === false) {
            return false;
        }
        
        return (bool)$Model->find('count', array(
            'conditions' => array(
                $Model->alias . '.' . $Model->primaryKey => $id,
                $Model->alias . '.' . $this->settings['foreignKey'] => $userId
            ),
            'recursive' => -1,
            'callbacks' => false
        ));
    }

}