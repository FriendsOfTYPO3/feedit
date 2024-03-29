.. include:: /Includes.rst.txt

======================
Basic Frontend Editing
======================

:Extension key:
   feedit

:Package name:
   friendsoftypo3/feedit

:Version:
   |release|

:Language:
   en

:Author:
   TYPO3 contributors

:License:
   This document is published under the
   `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
   license.

:Rendered:
   |today|

----

This TYPO3 extension is a simple solution to allow a logged in backend user to
edit content elements of a TYPO3 page via the frontend. It inserts editing icons
around elements for this purpose, which redirect the user to the corresponding
content element form in the backend.

The functionality was part of the TYPO3 Core until TYPO3 v10.0, and moved into
its own extension, receiving its own public repository.

----

**Table of Contents:**

.. contents::
   :backlinks: top
   :depth: 2
   :local:

Installation
============

The latest version can be installed via `TER`_ or via composer by running

.. code-block:: bash

   composer require friendsoftypo3/feedit

in a TYPO3 v10 installation.

.. _TER: https://extensions.typo3.org/extension/feedit

Current state
=============

The extension has been part of TYPO3 Core for a very long time but never
received much love. It may become more healthy if maintained as third-party
extension and has been extracted for this reason.

Contribution
============

Feel free to submit any pull request, or add documentation, tests, as you please.
We will publish a new version every once in a while, depending on the amount of
changes and pull requests submitted.

License
=======

The extension is published under GPL v2+, all included third-party libraries are
published under their respective licenses.

Authors
=======

Many contributors have been working on this area while this functionality was
part of the TYPO3 Core. This package is now maintained by a loose group of TYPO3
enthusiasts inside the TYPO3 Community. Feel free to contact `Benni Mack`_ for
any questions regarding `feedit`.

.. _Benni Mack: benni.mack@typo3.org
