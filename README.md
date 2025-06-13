# DominateAheadworksCompatibility/RewardPoints Extension

This Magento 2 extension provides compatibility between Aheadworks RewardPoints and Dominate checkout system.

## Overview

The extension creates a custom reward points field that integrates with the Dominate checkout iframe and uses Aheadworks' existing reward points functionality. It adds reward points handling to the IWD CheckoutConnector page via a separate JavaScript file.

## Architecture

- **Block Class**: `Block\Checkout\RewardPoints` - Main block class for reward points functionality
- **CustomDataProvider**: `Model\CustomDataProvider` - Sends collapsible HTML input field block to iframe
- **CartCustomDataProvider**: `Model\CartCustomDataProvider` - Sends dynamic variables and HTML block in cart totals section
- **Totals Integration**: `Model\Cart\CartTotals` - Extends IWD CartTotals to inject custom data
- **Templates**: `view/webapi_rest/templates/checkout/` - PHTML templates for rendering reward points UI
- **AW Extensions**: `Model\Calculator\Earning\EarnItemResolver\RawItemProcessor\InvoiceItemsResolver` & `Plugin\Model\Sales\InvoiceRepositoryPlugin` - Aheadworks compatibility
- **DI Configuration**: `etc/di.xml` - Dependency injection preferences
- **Iframe JavaScript**: Refactored client-side code for Dominate iframe integration (see Configuration section)

## Installation

1. Copy the extension files to `app/code/DominateAheadworksCompatibility/RewardPoints/`
2. Run Magento commands:
   ```bash
   php bin/magento module:enable DominateAheadworksCompatibility_RewardPoints
   php bin/magento setup:upgrade
   php bin/magento cache:flush
   ```

## Configuration

### JavaScript Integration

The following JavaScript code needs to be added to **Dominate Merchant Dashboard > Features > Custom Code > Body Code**:

```html
<script>
(function() {
    'use strict';
    
    // ==========================================
    // CONSTANTS & CONFIGURATION
    // ==========================================
    const CONFIG = {
        selectors: {
            loader: '.js-loader',
            input: 'input[name="reward_points_qty"]',
            actionBtn: '.js-reward-points-action-btn',
            wrapper: '.reward-points-wrapper',
            errorBlock: '.reward-points-error',
            toggleBlock: '.toggle-block',
            label: 'label'
        },
        classes: {
            error: 'error',
            hasError: 'has-error',
            filled: 'filled'
        },
        actions: {
            apply: 'apply',
            remove: 'remove'
        },
        keys: {
            enter: 13
        },
        messages: {
            empty: 'Please enter the number of points to apply.',
            invalid: 'Please enter a valid number.',
            negative: 'Please enter a positive number.',
            exceeds: 'You cannot apply more than {max} points.',
            applySuccess: 'Reward points applied successfully.',
            removeSuccess: 'Reward points removed successfully.',
            applyError: 'Failed to apply reward points.',
            removeError: 'Failed to remove reward points.'
        }
    };

    // ==========================================
    // CACHED DOM ELEMENTS
    // ==========================================
    const $jsLoader = $(CONFIG.selectors.loader);

    // ==========================================
    // UTILITY FUNCTIONS
    // ==========================================
    
    /**
     * Get form elements for a given form
     */
    function getFormElements($form) {
        return {
            $input: $form.find(CONFIG.selectors.input),
            $errorBlock: $form.find(CONFIG.selectors.errorBlock),
            $toggleBlock: $form.find(CONFIG.selectors.toggleBlock),
            $label: $form.find(CONFIG.selectors.label)
        };
    }

    /**
     * Clear error state for a form
     */
    function clearErrorState($form) {
        const elements = getFormElements($form);
        elements.$errorBlock.text('').hide();
        elements.$input.removeClass(CONFIG.classes.error);
        elements.$toggleBlock.removeClass(CONFIG.classes.hasError);
    }

    /**
     * Show error state for a form
     */
    function showError($form, message) {
        const elements = getFormElements($form);
        elements.$errorBlock.text(message).show();
        elements.$input.addClass(CONFIG.classes.error);
        elements.$toggleBlock.addClass(CONFIG.classes.hasError);
    }

    /**
     * Update UI state after successful operations
     */
    function updateUIState($form, newMaxAllowed) {
        const elements = getFormElements($form);
        elements.$label.addClass(CONFIG.classes.filled);
        clearErrorState($form);
        elements.$input.data('max-allowed', newMaxAllowed);
    }

    /**
     * Safe access to nested object properties
     */
    function getNestedProperty(obj, path, defaultValue = null) {
        return path.split('.').reduce((current, key) => {
            return (current && current[key] !== undefined) ? current[key] : defaultValue;
        }, obj);
    }

    // ==========================================
    // VALIDATION
    // ==========================================
    
    /**
     * Validate reward points input
     */
    function validateRewardPointsInput($form) {
        const elements = getFormElements($form);
        const inputValue = elements.$input.val().trim();
        const maxAllowed = parseInt(elements.$input.data('max-allowed'));

        // Check if empty
        if (!inputValue) {
            showError($form, CONFIG.messages.empty);
            return false;
        }

        // Check if numeric
        if (!/^\d+$/.test(inputValue)) {
            showError($form, CONFIG.messages.invalid);
            return false;
        }

        const pointsToApply = parseInt(inputValue);

        // Check if positive
        if (pointsToApply <= 0) {
            showError($form, CONFIG.messages.negative);
            return false;
        }

        // Check if not greater than max allowed
        if (pointsToApply > maxAllowed) {
            showError($form, CONFIG.messages.exceeds.replace('{max}', maxAllowed));
            return false;
        }

        return pointsToApply;
    }

    // ==========================================
    // REWARD POINTS OPERATIONS
    // ==========================================
    
    /**
     * Apply reward points
     */
    function applyRewardPoints(pointsQty) {
        $jsLoader.fadeIn();
        top.postMessage({
            applyRewardPoints: true,
            pointsQty: pointsQty
        }, '*');
    }

    /**
     * Remove reward points
     */
    function removeRewardPoints() {
        $jsLoader.fadeIn();
        top.postMessage({
            removeRewardPoints: true
        }, '*');
    }

    // ==========================================
    // UI STATE MANAGEMENT
    // ==========================================
    
    /**
     * Update all buttons to specific state
     */
    function updateAllButtons(action, text) {
        $(CONFIG.selectors.actionBtn).each(function() {
            const $button = $(this);
            $button.data('action', action);
            $button.find('span').text(text);
        });
    }

    /**
     * Update all inputs state
     */
    function updateAllInputs(disabled, value) {
        $(CONFIG.selectors.input).each(function() {
            const $input = $(this);
            $input.prop('disabled', disabled);
            if (value !== undefined) {
                $input.val(value);
            }
        });
    }

    /**
     * Handle successful apply operation
     */
    function handleRewardPointsApplySuccess(message) {
        updateAllButtons(CONFIG.actions.remove, 'Remove');
        updateAllInputs(true);
        $(document).trigger('updateTotals');
    }

    /**
     * Handle successful remove operation
     */
    function handleRewardPointsRemoveSuccess(message) {
        updateAllButtons(CONFIG.actions.apply, 'Apply');
        
        $(CONFIG.selectors.input).each(function() {
            const $input = $(this);
            const maxAllowed = $input.data('max-allowed') || '';
            $input.prop('disabled', false).val(maxAllowed);
        });
        
        $(document).trigger('updateTotals');
    }

    /**
     * Handle error operations
     */
    function handleRewardPointsError(message) {
        $jsLoader.fadeOut();
        $(CONFIG.selectors.wrapper).each(function() {
            showError($(this), message);
        });
    }

    // ==========================================
    // CHECKOUT DATA SYNCHRONIZATION
    // ==========================================
    
    /**
     * Get reward points variables from checkout data
     */
    function getRewardPointsVariables() {
        return getNestedProperty(window, 'checkoutData.cart.data.custom_data.variables', null);
    }

    /**
     * Update inputs based on checkout data
     */
    function syncRewardPointsInputs() {
        const variables = getRewardPointsVariables();
        
        if (!variables || variables.canApplyRewardPoints !== true) {
            return;
        }

        const $inputs = $(CONFIG.selectors.input);
        
        if ($inputs.length === 0) {
            return;
        }

        if (variables.areRewardPointsApplied === true) {
            // Applied state
            $inputs.each(function() {
                $(this).val(variables.appliedRewardPointsQty);
            });
        } else if (variables.areRewardPointsApplied === false) {
            // Not applied state
            $inputs.each(function() {
                const $input = $(this);
                const currentMaxAllowed = $input.data('max-allowed');
                const newMaxAllowed = variables.maxAllowedRewardPoints;
                
                if (currentMaxAllowed !== newMaxAllowed) {
                    $input.data('max-allowed', newMaxAllowed);
                    $input.val(newMaxAllowed);
                    
                    const $form = $input.closest(CONFIG.selectors.wrapper);
                    updateUIState($form, newMaxAllowed);
                }
            });
        }
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================
    
    /**
     * Handle Enter key press on reward points inputs
     */
    $(document).on('keypress', CONFIG.selectors.input, function(e) {
        if (e.which === CONFIG.keys.enter || e.keyCode === CONFIG.keys.enter) {
            e.preventDefault();
            
            const $input = $(this);
            const $form = $input.closest(CONFIG.selectors.wrapper);
            const $button = $form.find(CONFIG.selectors.actionBtn);
            
            $button.trigger('click');
        }
    });

    /**
     * Handle action button clicks
     */
    $(document).on('click', CONFIG.selectors.actionBtn, function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $form = $button.closest(CONFIG.selectors.wrapper);
        const action = $button.data('action');
        
        clearErrorState($form);
        
        if (action === CONFIG.actions.apply) {
            const pointsToApply = validateRewardPointsInput($form);
            if (pointsToApply !== false) {
                applyRewardPoints(pointsToApply);
            }
        } else if (action === CONFIG.actions.remove) {
            removeRewardPoints();
        }
    });

    /**
     * Handle checkout updates
     */
    $(document).on('checkoutUpdated', function() {
        syncRewardPointsInputs();
    });

    /**
     * Handle messages from parent window
     */
    window.addEventListener('message', function(event) {
        if (!event.data) return;
        
        const handlers = {
            rewardPointsApplySuccess: () => handleRewardPointsApplySuccess(event.data.message || CONFIG.messages.applySuccess),
            rewardPointsApplyError: () => handleRewardPointsError(event.data.message || CONFIG.messages.applyError),
            rewardPointsRemoveSuccess: () => handleRewardPointsRemoveSuccess(event.data.message || CONFIG.messages.removeSuccess),
            rewardPointsRemoveError: () => handleRewardPointsError(event.data.message || CONFIG.messages.removeError)
        };

        Object.keys(handlers).forEach(key => {
            if (event.data[key]) {
                handlers[key]();
            }
        });
    });

})();
</script>
```

### Parent Window Integration

The extension automatically adds reward points handling to the IWD CheckoutConnector page through a separate JavaScript file. No additional configuration is required for the parent window integration.

### PostMessage Communication

The JavaScript sends the following messages to the parent window:

**Apply Points:**
```javascript
{
    'applyRewardPoints': true,
    'pointsQty': 150
}
```

**Remove Points:**
```javascript
{
    'removeRewardPoints': true
}
```

**Expected Response Messages:**

Success responses:
```javascript
{
    rewardPointsApplySuccess: true,
    message: "150 reward points applied successfully!"
}

{
    rewardPointsRemoveSuccess: true,
    message: "Reward points removed successfully!"
}
```

Error responses:
```javascript
{
    rewardPointsApplyError: true,
    message: "Insufficient reward points balance."
}

{
    rewardPointsRemoveError: true,
    message: "Failed to remove reward points."
}
```

## Dependencies

- Aheadworks_RewardPoints
- Magento_Sales
- IWD_CheckoutConnector (for integration)

## File Structure

```
app/code/DominateAheadworksCompatibility/RewardPoints/
├── Block/Checkout/RewardPoints.php
├── Model/
│   ├── CustomDataProvider.php
│   ├── CartCustomDataProvider.php
│   ├── Cart/CartTotals.php
│   └── Calculator/Earning/EarnItemResolver/RawItemProcessor/InvoiceItemsResolver.php
├── Plugin/Model/Sales/InvoiceRepositoryPlugin.php
├── view/webapi_rest/templates/checkout/
│   ├── reward_points_field.phtml
│   └── reward_points_totals.phtml
├── etc/
│   ├── di.xml
│   └── module.xml
├── registration.php
├── composer.json
└── README.md
```

## Technical Implementation

### Data Synchronization
- **Automatic Updates**: Syncs with `window.checkoutData.cart.data.custom_data.variables` on checkout updates
- **Smart Updates**: Only modifies input values when max allowed points actually change
- **State Management**: Maintains consistent UI state across multiple form instances

### API Integration
- **PostMessage Communication**: Sends apply/remove requests to parent window via postMessage API
- **Response Handling**: Processes success/error responses with proper UI state updates
- **IWD Integration**: Uses IWD CheckoutConnector's CartTotals extension point to inject reward points data