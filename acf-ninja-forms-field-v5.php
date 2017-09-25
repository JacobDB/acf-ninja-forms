<?php

class acf_field_ninja_forms extends acf_field {

  /*
  *  __construct
  *
  *  This function will setup the field type data
  *
  *  @type  function
  *  @since 5.0.0
  *  @param n/a
  *  @return  n/a
  */

  function __construct()
  {
    // vars
    $this->name = 'ninja_forms_field';
    $this->label = __( 'Ninja Forms', 'acf-ninja-forms' );
    $this->category = __( 'Relational', 'acf' ); // Basic, Content, Choice, etc
    $this->defaults = array(
      'allow_null' => 0,
      'allow_multiple' => 0,
    );

    // do not delete!
    parent::__construct();
  }

  /*
   *  get_ninja_forms_version()
   *  Check Ninja Forms version
   *
   *  @type  function
   *  @since 1.0.3
   *  @param n/a
   *  @return  $version (int) the activate version of Ninja Forms
   */

   function get_ninja_forms_version()
   {
       return version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3', '<' ) || get_option( 'ninja_forms_load_deprecated', FALSE ) ? 2 : 3;
   }

  /*
  *  render_field_settings()
  *
  *  Create extra settings for your field. These are visible when editing a field
  *
  *  @type  action
  *  @since 3.6
  *  @date  23/01/13
  *
  *  @param $field (array) the $field being edited
  *  @return  n/a
  */

  function render_field_settings( $field ) {

    /*
    *  acf_render_field_setting
    *
    *  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
    *  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
    *
    *  More than one setting can be added by copy/paste the above code.
    *  Please note that you must also have a matching $defaults value for the field name (font_size)
    */

    acf_render_field_setting( $field, array(
      'label' => __( 'Allow Null?', 'acf' ),
      'type'  =>  'radio',
      'name'  =>  'allow_null',
      'choices' =>  array(
        1 =>  __( 'Yes', 'acf' ),
        0 =>  __( 'No', 'acf' ),
      ),
      'layout'  =>  'horizontal'
    ));

    acf_render_field_setting( $field, array(
      'label' => __( 'Select multiple values?', 'acf' ),
      'type'  =>  'radio',
      'name'  =>  'allow_multiple',
      'choices' =>  array(
        1 =>  __( 'Yes', 'acf' ),
        0 =>  __( 'No', 'acf' ),
      ),
      'layout'  =>  'horizontal'
    ));

  }

  /*
  *  render_field()
  *
  *  Create the HTML interface for your field
  *
  *  @param $field (array) the $field being rendered
  *
  *  @type  action
  *  @since 3.6
  *  @date  23/01/13
  *
  *  @param $field (array) the $field being edited
  *  @return  n/a
  */

  function render_field( $field ) {


    /*
    *  Review the data of $field.
    *  This will show what data is available
    */

    // vars
    $nf_version = $this->get_ninja_forms_version();
    $field = array_merge($this->defaults, $field);
    $choices = array();
    $forms = $nf_version === 2 ? ninja_forms_get_all_forms() : Ninja_Forms()->form()->get_forms();
    $multiple = ( $field['allow_multiple'] == true ? ' multiple' : '');
    $field_name = ( $field['allow_multiple'] == true ? $field['name'] . '[]' : $field['name'] );

    if ( $forms ) {
      foreach( $forms as $form ) {
        if ($nf_version === 2) {
          $choices[ $form[ 'id' ] ] = ucfirst( $form[ 'data' ][ 'form_title' ] );
        } else {
            $choices[ $form->get_id() ] = ucfirst( $form->get_setting( 'title' ) );
        }
      }
    }

    // Override field settings and render
    $field['choices'] = $choices;
    $field['type'] = 'select';
    ?>

      <select name="<?php echo $field_name; ?>" id="<?php echo $field['name'];?>"<?php echo $multiple; ?>>
        <?php
          if ( $field['allow_null'] == true ) :
            $selected = '';
            if ( is_array( $field['value'] ) ) {
              if ( in_array( '', $field['value'] ) ) {
                $selected = ' selected="selected"';
              }
            } else {
              if ( $field['value'] == '' ) {
                $selected = ' selected="selected"';
              }
            }
            ?>
            <option value="" <?php echo $selected; ?>><?php _e( '- Select -', 'acf' ); ?></option>
          <?php
          endif;
          foreach ( $field['choices'] as $key => $value ) :
            $selected = '';
            if ( is_array( $field['value'] ) ) {
              if ( in_array( $key, $field['value'] ) ) {
                $selected = ' selected="selected"';
              }
            } else {
              if ( $field['value'] == $key ) {
                $selected = ' selected="selected"';
              }
            }
            ?>
            <option value="<?php echo $key; ?>"<?php echo $selected; ?>>
              <?php echo $value; ?>
            </option>
          <?php endforeach;
        ?>
      </select>
    <?php
  }

  /*
  *  format_value()
  *
  *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
  *
  *  @type  filter
  *  @since 3.6
  *  @date  23/01/13
  *
  *  @param $value (mixed) the value which was loaded from the database
  *  @param $post_id (mixed) the $post_id from which the value was loaded
  *  @param $field (array) the field array holding all the field options
  *
  *  @return  $value (mixed) the modified value
  */

  function format_value( $value, $post_id, $field ) {
    $nf_version = $this->get_ninja_forms_version();

    if ( ! $value ) {
      return false;
    }

    if ( $value == 'null' ) {
      return false;
    }

    if ( is_array( $value ) ) {
      foreach( $value as $k => $v ) {
        if ($nf_version === 2) {
          $form = ninja_forms_get_form_by_id( $v );
        } else {
          $form_object = Ninja_Forms()->form( $v )->get();
          $form = array( 'id' => $v, 'data' => $form_object->get_settings(), 'date_updated' => $form_object->get_setting( 'date_updated' ) );
        }

        $value[ $k ] = $form;
      }
    } else {
      if ($nf_version === 2) {
        $value = ninja_forms_get_form_by_id( $value );
      } else {
        $form_object = Ninja_Forms()->form( $value )->get();
        $value = array( 'id' => $value, 'data' => $form_object->get_settings(), 'date_updated' => $form_object->get_setting( 'date_updated' ) );
      }
    }

    return $value;
  }
}

new acf_field_ninja_forms();
