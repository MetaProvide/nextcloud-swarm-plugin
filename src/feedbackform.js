/*
 * @copyright Copyright (c) 2024, MetaProvide Holding EKF
 *
 * @author Ron Trevor <ecoron@proton.me> @author
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { subscribe } from '@nextcloud/event-bus';
import { registerDavProperty } from '@nextcloud/files';

registerDavProperty("nc:ethswarm-node");

// //////////////////////////////////////////////////
// Common functions for manipulating New file menu entries.
let previousPathIsSwarm = false;
let feedbackformLoaded = false;
console.log('Hejbit-files-feedback-form:previousPathIsSwarm=' + previousPathIsSwarm );

// TODO - Try adding email getuser from oc object or from another request before sending the request
// TODO - Send the request to NC first  and get the user from the backend and Remove CSP
// TODO - Fix CSS issues
// TODO - Remove wiget when not is not in swarm folders




// Listeners to detect changes in listing.
subscribe('files:list:updated', (data) => {
	if (typeof(data.folder) === 'undefined') {
		// Not a valid response so ignore.
		return;
	}

	let currentPathIsSwarm = false;
	if (data.folder?.attributes["ethswarm-node"]){
		currentPathIsSwarm = true;
	}

	console.log('Hejbit-files-feedback-form:list:updated=previousPathIsSwarm=' + previousPathIsSwarm + ";currentPathIsSwarm=" + currentPathIsSwarm );
	// First condition checks for 1st navigation in Swarm storage
	// 2nd condition is for direct navigation by URL
	if ((currentPathIsSwarm && !previousPathIsSwarm) || (currentPathIsSwarm && previousPathIsSwarm) && !feedbackformLoaded) {
		console.log("Swarm entry - Show feedback form");


            const options = {
                id: 'feedback',
                endpoint: 'https://test.hejbit.com/api/feedback',
                emailField: false,
                events: true,
                forceShowButton: false,
                types: {
                    general: {
                        text: 'General Feedback',
                        icon: '📝'
                    },
                    idea: {
                        text: 'Idea',
                        icon: '💡'
                    },
                    bug: {
                        text: 'Issue',
                        icon: '⚠️'
                    }
                },
                btnTitle: 'Feedback',
                title: '<b>💬 HejBit Feedback</b>',
                inputPlaceholder: 'We welcome your feedback here.',
                // emailPlaceholder: 'Email address (optional)',
                submitText: 'Submit',
                	backText: 'Back',
                // contactText: 'Or send an email!',
                // contactLink: 'mailto:bug@webit.ws',
                typeMessage: 'How can we improve?',
                success: 'We Appreciate Your Feedback!',
                failedTitle: 'Oops, an error ocurred!',
                failedMessage: 'Please try again. If this keeps happening, try to send an email instead.',
                position: 'right',
                primary: '#0d6efd',
                background: '#fff',
                color: '#000'
            };

            try {
                console.log('Starting Feedback...');
                const feedback = new window.Feedback(options);
                console.log('Feedback:', feedback);
                feedback.renderButton();
				feedbackformLoaded = true;
            } catch (error) {
                console.error('Error:', error);
            }

			window.addEventListener('feedback-submit', (event) => {
				const feedbackData = event.detail;

				// Get request token for Nextcloud
				const requestToken = OC.requestToken;

				fetch(OC.generateUrl('/apps/your-app/endpoint'), {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'RequestToken': requestToken,
						'Accept': 'application/json'
					},
					body: JSON.stringify(feedbackData)
				})
				.then(response => response.json())
				.then(data => {
					console.log('Success:', data);
				})
				.catch((error) => {
					console.error('Error:', error);
				});
			});


	} else if (!currentPathIsSwarm && !previousPathIsSwarm) {
		console.log("Default entry - Don't Show feedback form");
	}
	previousPathIsSwarm = currentPathIsSwarm;
});
