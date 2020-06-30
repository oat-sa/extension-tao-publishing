# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Removed
- “Sync to remote environments” checkboxes.
- "SyncDeliveryEnvironments" task
- Old Delivery Event Listeners
- Origin Delivery ID Field
### Added
- Changelog File
- New Listener "DeliveryTestSaverListener" which listen the "DeliveryCreatedEvent"
- New Service "DeliveryTestService" which handle the test export during delivery creation.
- New controller/action which handles the creation of DeployTestEnvironments task 
- Changelog Filefeature/TAO-10137/publish-to-remote-delivery-environment
- New controller/action "listPublicationTargets" which render environments checkbox form

>>>>>>> 3a8e91a2ab3f07caf04dc7635482291bced3c133

## [2.1.2] - 2019-10-25
### Removed
- Delivery Label Synchronisation