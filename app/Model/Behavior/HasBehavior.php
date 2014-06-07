<?php
/**
 * Provides common functions for a ModelA that hasMany ModelB.
 *
 * @author https://github.com/bretten
 */
App::uses('ModelBehavior', 'Model');

class HasBehavior extends ModelBehavior {

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
 * saveDiff method
 *
 * Removes all associated hasMany rows that are not present in the data.
 *
 * @param Model $Model Model instance
 * @param array $data The data to be saved.
 * @param array $options Options passed from Model::save().
 * @return mixed
 */
    public function saveDiff(Model $Model, $data = array(), $options = array()) {
        // Make sure the hasMany Model was specified
        if (!isset($options['removeHasMany']) || !$options['removeHasMany']) {
            return false;
        }

        // The hasMany Model
        $hasManyName = $options['removeHasMany'];

        // Check that the hasMany Model is a valid relationship
        if (!array_key_exists($hasManyName, $Model->hasMany)) {
            return false;
        }

        $dataSource = $Model->getDataSource();

        // Begin the transaction
        $dataSource->begin();

        // Flag to indicate a commit
        $commit = true;

        // Check for an existing record of the Model and its hasMany Model
        if (isset($data[$Model->name][$Model->primaryKey]) && $data[$Model->name][$Model->primaryKey]) {
            // Get the POSTed hasMany id's
            $in = Set::extract("/{$hasManyName}/{$Model->{$hasManyName}->primaryKey}[{$Model->{$hasManyName}->primaryKey}>0]", $data);
            // Get the stored hasMany id's
            $modelData = $Model->find('first', array(
                'conditions' => array(
                    $Model->name . "." . $Model->primaryKey => $data[$Model->name][$Model->primaryKey]
                ),
                'fields' => array(
                    $Model->name . "." . $Model->primaryKey
                ),
                'contain' => array(
                    $hasManyName => array(
                        'fields' => array(
                            $hasManyName . "." . $Model->{$hasManyName}->primaryKey
                        )
                    )
                )
            ));
            $stored = Set::extract("/{$hasManyName}/{$Model->{$hasManyName}->primaryKey}[{$Model->{$hasManyName}->primaryKey}>0]", $modelData);

            // Calculate the diff between the two Sets of hasMany ids
            $diff = array_diff($stored, $in);

            // DELETE the missing hasManys
            if (!$Model->{$hasManyName}->deleteAll(array($hasManyName . "." . $Model->{$hasManyName}->primaryKey => $diff), true)) {
                $commit = false;
            }
        }

        // Normal saveAll
        if (!$result = $Model->saveAll($data, $options)) {
            $commit = false;
        }

        // End the transaction
        if ($commit) {
            $dataSource->commit();
        } else {
            $dataSource->rollback();
        }

        return $result;
    }
}