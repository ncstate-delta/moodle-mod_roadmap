<?php




/**
 * List of features supported in Roadmap module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function roadmap_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_NO_VIEW_LINK:            return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        default: return false;
    }
}



/**
 * Add roadmap instance.
 * @param object $data
 * @param object $mform
 * @return int new folder instance id
 */
function roadmap_add_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();

    $data->id = $DB->insert_record('roadmap', $data);

    return $data->id;
}


/**
 * Update roadmap instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function roadmap_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('roadmap', $data);

    return true;
}

/**
 * Delete roadmap instance.
 * @param int $id
 * @return bool true
 */
function roadmap_delete_instance($id) {
    global $DB;

    if (!$roadmap = $DB->get_record('roadmap', array('id'=>$id))) {
        return false;
    }

    $DB->delete_records('roadmap', array('id' => $roadmap->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function roadmap_get_coursemodule_info($coursemodule) {
    global $DB, $CFG;

    if ($roadmap = $DB->get_record('roadmap', array('id' => $coursemodule->instance), 'id, name, intro, introformat')) {

        $info = new cached_cm_info();
        // no filtering hre because this info is cached and filtered later
        $info->content = format_module_intro('roadmap', $roadmap, $coursemodule->id, false);

        $info->content .= 'ROADMAP HERE';

        // Show configuration link if editing is on.
        if (true) {
            $info->content .= '<div><a href="' . $CFG->wwwroot . '/mod/roadmap/configuration.php?id=' . $coursemodule->id . '">Configure Roadmap</a></div>';
        }

        $info->name  = $roadmap->name;
        return $info;
    } else {
        return null;
    }
}