jQuery(document).ready(function($) {
    var formIndex = 1; // Counter to track the number of forms

    // Function to initialize the select options behavior
    function initializeSelectOptions(select) {
        $(select).find("option").off("mousedown mouseup").on("mousedown", function() {
            var $me = $(this);
            $me.data("was-selected", $me.prop("selected"));
            $(select).data("selected", $(select).find("option:selected"));
        }).on("mouseup", function() {
            var $me = $(this);
            $(select).data("selected").prop("selected", true);
            $me.prop("selected", !$me.data("was-selected"));
        });
    }

    // Add a new set of fields
    $('#add-page-form').on('click', function(e) {
        e.preventDefault();

        // Clone the first form section
        var newForm = $('.page-form:first').clone();

        // Update IDs and names of fields to be unique
        newForm.find('input, textarea, select').each(function() {
            var newId = $(this).attr('id').replace(/_\d+/, '_' + formIndex);
            var newName = $(this).attr('name').replace(/\[\d+\]/, '[' + formIndex + ']');

         
            $(this).attr('id', newId).attr('name', newName); // Update IDs and names
            
            // Clear input and textarea values
            if ($(this).is('input, textarea')) {
                $(this).val(''); // Clear values
            } 
            // Deselect options in the multiple select
            else if ($(this).is('select[multiple]')) {
                $(this).find('option').prop('selected', false); // Deselect options
            }
        });

        // Update the title of the new section
        newForm.find('h3').text('New Page ' + (formIndex + 1));
        newForm.append('<button type="button" class="remove-page-form button">Supprimer</button><br><br>');

        // Append the new section to the container
        $('#repeater-container').append(newForm);

        // Initialize select options for the new form
        initializeSelectOptions(newForm.find("select"));

        formIndex++;
    });

    $('#repeater-container').on('click', '.remove-page-form', function() {
        $(this).closest('.page-form').remove(); // Remove the closest page-form
     // Recalculate form indices
     formIndex--
     updateFormIndices();
    });

    // Function to update form indices
    function updateFormIndices() {
        $('#repeater-container .page-form').each(function(index) {
            $(this).find('input, textarea, select').each(function() {
                var newId = $(this).attr('id').replace(/_\d+/, '_' + index);
                var newName = $(this).attr('name').replace(/\[\d+\]/, '[' + index + ']');


                $(this).attr('id', newId).attr('name', newName); // Update IDs and names
            });
            $(this).find('h3').text('New Page ' + (index + 1)); // Update title
        });
    }

    // Initialize select options for the existing select elements
    $("select").each(function() {
        initializeSelectOptions(this);
    });

    
});