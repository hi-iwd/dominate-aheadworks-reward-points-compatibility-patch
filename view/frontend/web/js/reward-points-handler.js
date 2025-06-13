/**
 * Reward Points Handler for Dominate + Aheadworks Integration
 * Listens for postMessage events from iframe and handles reward points apply/remove
 */
require([
    'mage/storage'
], function (storage) {
    'use strict';

    /**
     * Extract error message from response
     */
    function extractErrorMessage(response, defaultMessage) {
        if (response && response.responseJSON && response.responseJSON.message) {
            return response.responseJSON.message;
        } else if (response && response.responseText) {
            try {
                var errorData = JSON.parse(response.responseText);
                if (errorData.message) {
                    return errorData.message;
                }
            } catch (e) {
                // Use default message
            }
        }
        return defaultMessage;
    }

    /**
     * Handle reward points messages from iframe
     */
    function handleRewardPointsMessage(event) {
        if (!event.data) {
            return;
        }

        // Handle apply reward points
        if (event.data.applyRewardPoints && event.data.pointsQty) {
            var pointsQty = parseInt(event.data.pointsQty);
            if (pointsQty > 0) {
                applyRewardPoints(pointsQty, event.source);
            } else {
                sendRewardPointsError('Invalid points quantity.', event.source);
            }
        }
        // Handle remove reward points
        else if (event.data.removeRewardPoints) {
            removeRewardPoints(event.source);
        }
    }

    /**
     * Apply reward points using direct API
     */
    function applyRewardPoints(pointsQty, iframe) {
        var url = 'rest/default/V1/awRp/carts/mine/apply/' + pointsQty;
        
        return storage.put(url, {}, true).done(function (response) {
            // Send success message back to iframe
            var successMessage = 'Reward points applied successfully.';
            if (response && response[0] && response[0].message) {
                successMessage = response[0].message;
            }
            
            iframe.postMessage({
                rewardPointsApplySuccess: true,
                message: successMessage
            }, '*');
        }).fail(function (response) {
            var errorMessage = extractErrorMessage(response, 'Failed to apply reward points.');
            sendRewardPointsError(errorMessage, iframe);
        });
    }

    /**
     * Remove reward points using direct API
     */
    function removeRewardPoints(iframe) {
        var url = 'rest/default/V1/awRp/carts/mine/remove';
        
        return storage.delete(url, true).done(function (response) {
            // Send success message back to iframe
            iframe.postMessage({
                rewardPointsRemoveSuccess: true,
                message: 'Reward points removed successfully.'
            }, '*');
        }).fail(function (response) {
            var errorMessage = extractErrorMessage(response, 'Failed to remove reward points.');
            sendRewardPointsError(errorMessage, iframe);
        });
    }

    /**
     * Send error message back to iframe
     */
    function sendRewardPointsError(message, iframe) {
        iframe.postMessage({
            rewardPointsApplyError: true,
            message: message
        }, '*');
    }

    // Add reward points message listener
    if (window.addEventListener) {
        window.addEventListener("message", handleRewardPointsMessage, false);
    } else if (window.attachEvent) {
        window.attachEvent("onmessage", handleRewardPointsMessage);
    }
}); 