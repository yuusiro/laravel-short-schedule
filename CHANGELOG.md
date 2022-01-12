# Changelog

All notable changes to `laravel-short-schedule` will be documented in this file

## 1.4.7 - 2022-01-12

- PendingShortScheduleCommand::command method will attempt to resolve command name if class name was given

**Full Changelog**: https://github.com/dima-bzz/laravel-short-schedule/compare/1.4.6...1.4.7

## 1.4.6 - 2021-09-01

- added an output message to the console when starting and restarting the worker

## 1.4.5 - 2021-08-31

- updated description for restart command and update Readme

## 1.4.4 - 2021-08-31

- updated composer.json and Readme

## 1.4.3 - 2021-08-27

- added command restart for worker daemons

## 1.4.2 - 2021-06-11

- allow spatie/temporary-directory 2.* (#35)

## 1.4.1 - 2021-06-04

- do not set a default lifetime for production

## 1.4.0 - 2021-05-31

- add lifetime option

## 1.3.0 - 2020-12-24

- add PHP8 support (#25)

## 1.2.2 - 2020-09-08

- add support for Laravel 8

## 1.2.1 - 2020-07-14

- fix for tasks not getting executed if the command is started by supervisord.

## 1.2.0 - 2020-07-13

- added `onOneServer` option to short run commands (#8)

## 1.1.0 - 2020-06-17

- adding support for Maintenance Mode (#4)

## 1.0.0 - 2020-06-07

- initial release
