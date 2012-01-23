Feature: Generate
    In order to modify the database
    As a developer
    I want to generate a migration file

    Scenario: Generate a simple file
        When I run `generate.php create_cucumber`
        Then it should pass whith:
        """

        Created migration: 20120123062625_CreateCucumber.php

        """

    Scenario: Generate a duplicate migration class name
        When I run `generate.php create_cucumber`
        Then it should fail whith:
        """

            This class name is already used. Please, choose another name.

        """
