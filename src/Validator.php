<?php

namespace App;

class Validator
{
    public function validate(array $user)
    {
        // BEGIN (write your solution here)
        $errors = [];
        if ($user['name'] == '') {
            $errors['name'] = "Can't be blank";
        } if ($user['email'] == '') {
            $errors['email'] = "Can't be blank";
        }
        return $errors;
        // END
    }
}