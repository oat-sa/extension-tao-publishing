# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.2]
### Removed
- *Sync to remote environments* checkboxes
- *Actions* block on the remote environment form.
- *DeliveryCreatedEvent* and *DeliveryUpdatedEvent* listeners to trigger remote environment's actions  
- Configuration of the *RemoteSync* delivery properties in the *DeliveryFactory*
### Added
- A new event handler to create a backup of the QTI test package when a delivery is created
- A new property of Remote Environment - `Publishing Enabled`
- A new UI interface to publish delivery to selected remote environment(s)
- API to get a list of remote environments
- API to publish deliveries to remote environments
### Added
- Changelog File

## [2.1.2] - 2019-10-25
### Removed
- Delivery Label Synchronisation