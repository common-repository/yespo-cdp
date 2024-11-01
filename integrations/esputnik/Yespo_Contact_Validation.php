<?php

namespace Yespo\Integrations\Esputnik;

class Yespo_Contact_Validation
{
    const STRING_LENGTH = "/^.{2,40}$/u";
    const APOSTROPHE = "/^(?!['`’]).*(?<!['`’])$/";
    const HYPHEN = "/^(?!-).*(?<!-)$/";
    const LIMIT_NUMBERS_STRING = '/^(?:\D*\d){0,3}\D*$/';
    const ANY_FIGURE_SYMBOL = '/(?=.*\D)/';
    const SPECIAL_CHARACTERS_ARRAY = [ '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '=', '+', '[', '{', ']', '}', '\\', '|', ';', ':', ',', '<', '>', '/', '?', '"'];

    public static function name_validation(string $name){
        return self::validation_process($name, 'name');
    }
    public static function lastname_validation(string $name){
        return self::validation_process($name, 'lastname');
    }

    /*** validation process ***/
    private static function validation_process(string $word, string $type){
        $validator = new self();
        if($validator->length_unicode_validation($word) && $validator->limit_numbers_inside_word($word)){
            if($validator->can_split_string($word)) return $validator->check_array_requirements($validator->split_string_to_array($word), $type);
            else return $validator->validation_algorithm($word);
        }
        return false;
    }

    /*** validation algorithm ***/
    private function validation_algorithm(string $word){
        if ($this->has_apostrophe_inside_word($word)) {
            if ($this->has_hyphen_inside_word($word)) {
                if ($this->has_point_at_the_end($word)) {
                    if ($this->limit_numbers_inside_word($word)) {
                        return $this->checking_special_chracters($word);
                    }
                }
            }
        }
        return false;
    }

    /*** VALIDATION RULES ***/
    /*** checking unicode length ***/
    private function length_unicode_validation(string $name){
        return $this->check_string_function($name, self::STRING_LENGTH);
    }
    /*** checking for apostrophe ***/
    private function has_apostrophe_inside_word(string $word){
        return $this->check_string_function($word,self::APOSTROPHE);
    }
    /*** checking for hyphen ***/
    private function has_hyphen_inside_word(string $word){
        return $this->check_string_function($word,self::HYPHEN);
    }
    /*** checking for point at the end ***/
    private function has_point_at_the_end(string $word){
        $length = strlen($word) - 1;
        for($i = 0; $i < $length; $i++){
            if($word[$i] === '.') return false;
        }
        if($word[$length] === '.') {
            if ($length > 2 || $length < 1) return false;
        }
        return true;
    }
    /*** checking for limit numbers ***/
    private function limit_numbers_inside_word(string $word){
        if($this->check_string_function($word,self::LIMIT_NUMBERS_STRING)){
            return $this->check_string_function($word, self::ANY_FIGURE_SYMBOL );
        } else return false;
    }
    /*** checking for special characters ***/
    private function checking_special_chracters(string $word){
        foreach (self::SPECIAL_CHARACTERS_ARRAY as $character) {
            if (strpos($word, $character) !== false) return false;
        }
        return true;
    }

    /*** SERVICES FUNCTIONS ***/
    /*** checking opportunity create array ***/
    private function can_split_string(string $string){
        if (strpos($string, ' ') !== false) return true;
        else return false;
    }
    /*** create array from string ***/
    private function split_string_to_array(string $string){
        return explode(' ', $string);
    }
    /*** check array to requirements ***/
    private function check_array_requirements(array $array, string $type) {
        $longerThanThree = 0;
        $shorterThanOrEqualToThree = 0;

        foreach ($array as $element) {
            if($this->validation_algorithm($element)) {
                if (strlen($element) > 3) $longerThanThree++;
                else $shorterThanOrEqualToThree++;
            } else return false;
        }

        if ( ($longerThanThree <= 3 && $shorterThanOrEqualToThree <= 3) || ($type === 'lastname') ) return true;
        else return false;
    }
    /*** checking regular rule ***/
    private function check_string_function(string $string, string $regex){
        if (preg_match($regex, $string)) return true;
        else return false;
    }
}