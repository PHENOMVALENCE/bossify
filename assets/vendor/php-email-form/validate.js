/**
* PHP Email Form Validation - v3.9
* Updated to handle JSON API responses correctly
* URL: https://bootstrapmade.com/php-email-form/
* Author: BootstrapMade.com
*/
(function () {
  "use strict";

  let forms = document.querySelectorAll('.php-email-form');

  forms.forEach( function(e) {
    e.addEventListener('submit', function(event) {
      event.preventDefault();

      let thisForm = this;

      let action = thisForm.getAttribute('action');
      let recaptcha = thisForm.getAttribute('data-recaptcha-site-key');
      
      if( ! action ) {
        displayError(thisForm, 'The form action property is not set!');
        return;
      }
      
      // Hide previous messages
      const loading = thisForm.querySelector('.loading');
      const errorMessage = thisForm.querySelector('.error-message');
      const sentMessage = thisForm.querySelector('.sent-message');
      
      if (loading) loading.classList.add('d-block');
      if (errorMessage) {
        errorMessage.classList.remove('d-block');
        errorMessage.textContent = '';
      }
      if (sentMessage) {
        sentMessage.classList.remove('d-block');
        sentMessage.textContent = '';
      }

      let formData = new FormData( thisForm );

      if ( recaptcha ) {
        if(typeof grecaptcha !== "undefined" ) {
          grecaptcha.ready(function() {
            try {
              grecaptcha.execute(recaptcha, {action: 'php_email_form_submit'})
              .then(token => {
                formData.set('recaptcha-response', token);
                php_email_form_submit(thisForm, action, formData);
              })
            } catch(error) {
              displayError(thisForm, error);
            }
          });
        } else {
          displayError(thisForm, 'The reCaptcha javascript API url is not loaded!')
        }
      } else {
        php_email_form_submit(thisForm, action, formData);
      }
    });
  });

  function php_email_form_submit(thisForm, action, formData) {
    fetch(action, {
      method: 'POST',
      body: formData,
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(response => {
      // Always get response as text first
      return response.text().then(text => {
        // Trim whitespace
        text = text.trim();
        
        // Try to parse as JSON
        let jsonData = null;
        try {
          jsonData = JSON.parse(text);
        } catch (e) {
          // Not JSON, will handle below
        }
        
        // If we successfully parsed JSON
        if (jsonData !== null && typeof jsonData === 'object') {
          // Check if it's a success response
          if (jsonData.success === true) {
            return {
              success: true,
              message: jsonData.message || 'Form submitted successfully!',
              data: jsonData
            };
          } else {
            // It's an error response
            let errorMsg = jsonData.message || 'Form submission failed';
            if (jsonData.errors) {
              const errorList = Object.values(jsonData.errors).join(', ');
              errorMsg = errorList || errorMsg;
            }
            throw new Error(errorMsg);
          }
        } else {
          // Not JSON - check for legacy "OK" response
          if (text === 'OK') {
            return {
              success: true,
              message: 'Form submitted successfully!',
              data: text
            };
          } else {
            // Try one more time to parse as JSON (in case of whitespace issues)
            try {
              const retryJson = JSON.parse(text);
              if (retryJson.success === true) {
                return {
                  success: true,
                  message: retryJson.message || 'Form submitted successfully!',
                  data: retryJson
                };
              }
            } catch (e) {
              // Still not JSON
            }
            // It's an error
            throw new Error(text || 'An error occurred');
          }
        }
      }).catch(error => {
        // If there's an error in the text parsing, try to extract JSON from error message
        const errorText = error.message || String(error);
        
        // Try to find JSON in the error text
        const jsonMatch = errorText.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
          try {
            const jsonData = JSON.parse(jsonMatch[0]);
            if (jsonData.success === true) {
              return {
                success: true,
                message: jsonData.message || 'Form submitted successfully!',
                data: jsonData
              };
            }
          } catch (e) {
            // Not valid JSON
          }
        }
        
        // Re-throw the original error
        throw error;
      });
    })
    .then(result => {
      // SUCCESS - Show success message
      const loading = thisForm.querySelector('.loading');
      const errorMessage = thisForm.querySelector('.error-message');
      const sentMessage = thisForm.querySelector('.sent-message');
      
      if (loading) loading.classList.remove('d-block');
      
      // Clear error message
      if (errorMessage) {
        errorMessage.classList.remove('d-block');
        errorMessage.textContent = '';
      }
      
      // Show success message
      if (sentMessage) {
        sentMessage.textContent = result.message || 'Form submitted successfully!';
        sentMessage.classList.add('d-block');
      }
      
      // Reset form
      thisForm.reset();
    })
    .catch((error) => {
      // ERROR - Show error message
      displayError(thisForm, error);
    });
  }

  function displayError(thisForm, error) {
    const loading = thisForm.querySelector('.loading');
    const errorMessage = thisForm.querySelector('.error-message');
    const sentMessage = thisForm.querySelector('.sent-message');
    
    if (loading) loading.classList.remove('d-block');
    if (sentMessage) {
      sentMessage.classList.remove('d-block');
      sentMessage.textContent = '';
    }
    
    // Extract error message
    let errorMsg = '';
    
    if (error instanceof Error) {
      errorMsg = error.message;
    } else if (typeof error === 'string') {
      errorMsg = error;
    } else {
      errorMsg = String(error);
    }
    
    // Remove "Error: " prefix if present
    if (errorMsg && errorMsg.startsWith('Error: ')) {
      errorMsg = errorMsg.substring(7).trim();
    }
    
    // Try to extract and parse JSON from error message
    if (errorMsg) {
      // Look for JSON object in the error message
      const jsonMatch = errorMsg.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        try {
          const jsonData = JSON.parse(jsonMatch[0]);
          if (jsonData.success === true) {
            // This is actually a success! Show success message
            if (sentMessage) {
              sentMessage.textContent = jsonData.message || 'Form submitted successfully!';
              sentMessage.classList.add('d-block');
            }
            if (errorMessage) {
              errorMessage.classList.remove('d-block');
              errorMessage.textContent = '';
            }
            thisForm.reset();
            return; // Exit early
          } else if (jsonData.message) {
            errorMsg = jsonData.message;
          } else if (jsonData.errors) {
            errorMsg = Object.values(jsonData.errors).join(', ');
          }
        } catch (e) {
          // Not valid JSON, continue with original error
        }
      }
    }
    
    // Display error (only if we haven't shown success)
    if (errorMessage && errorMsg) {
      errorMessage.textContent = errorMsg || 'An error occurred. Please try again.';
      errorMessage.classList.add('d-block');
    }
  }

})();
