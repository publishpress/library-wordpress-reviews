# Changelog

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.0]

- ADDED: New languages file for German, Finnish, Filipino, Indonesian, Japanese, Korean, Portuguese (Brazil), Russian, and Yoruba
- ADDED: Translation workflow scripts via composer 
- ADDED: dev-workspace with Docker and PHPStan configuration

## [1.1.21]

- FIXED: Improve nonce verification on ajax handler method.

## [1.1.20]

- FIXED: Fix the fatal error related to the class ReviewsController when its file is required by different plugins, trying to declare a class that already exists, #12.

## [1.1.19]

- ADDED: Add .gitattributes and distignore files to remove files we don't want to be included in the final distributions

## [1.1.18]

- FIXED: Remove the $apiUrl var and a dead code, #7

## [1.1.17]

- FIXED: Fix warning about undefined array key for the plugin slug, #4

## [1.1.16]

- FIXED: Fix the method to get current user

## [1.1.15]

- FIXED: Fixed code style

## [1.1.14]

- FIXED: Change conditional to display the banner only for admins, #5

## [1.1.13]

- FIXED: Fixed PHP warning if the trigger was not loaded yet for the plugin, #3

## [1.1.12]

- FIXED: Fix conflict when using this library in multiple plugins, #2
