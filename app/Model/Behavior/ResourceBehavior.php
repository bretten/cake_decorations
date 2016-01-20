<?php
/**
 * Models acting as a Resource are treated like a HTTP resource.
 *
 * @author https://github.com/bretten
 */
App::uses('ModelBehavior', 'Model');

class ResourceBehavior extends ModelBehavior {

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
     * Before save method
     *
     * @param Model $Model Model instance
     * @param array $options Options passed from Model::save().
     * @return boolean true to continue, false to abort the save
     */
    public function beforeSave(Model $Model, $options = array()) {
        // Generate a new ETag
        if ((isset($this->settings['autoSave']) && $this->settings['autoSave'] === true)
            || (isset($options['saveEtag']) && $options['saveEtag'] === true)
        ) {
            if (isset($Model->data[$Model->alias])) {
                $Model->data[$Model->alias][$this->settings['etagField']] = md5(time());
            } else {
                $Model->data[$this->settings['etagField']] = md5(time());
            }
        }
        return true;
    }

}
