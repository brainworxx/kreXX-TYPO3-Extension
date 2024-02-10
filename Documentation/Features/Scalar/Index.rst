.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _scalar:

Scalar analysis
===============

A string value can host a lot of information. When dealing with a webservice, there is a good chance that you will get a json or xml as an answer.
Taking a closer look at these may reveal a lot of information.


JSON
----

When kreXX encounters a :literal:`JSON`, it does the following:

- Decode it and analyse it's content.
- Offer source generation to reach the decoded values.
- Output a pretty print for better readability
- Output the original string

|

.. figure:: ../../Images/Scalar/sample_data_json.png
	:width: 833px
	:alt: Code generation for a lorem ipsum sample data json

	Code generation for a lorem ipsum sample data json.

XML
---

When kreXX encounters a :literal:`XML`, it does the following:

- Parse it into an array and analyse it's content
- Output a pretty print for better readability
- Output the original string

|

.. figure:: ../../Images/Scalar/sample_data_xml.png
	:width: 648px
	:alt: Pretty print for a lorem ipsum sample data xml

	Pretty print for a lorem ipsum sample data xml.

Filepath
--------

When kreXX encounters a file path, it does the following:

- Retrieve the realpath() and output it
- Analyse the mime type of the file

Callback
--------

When kreXX encounters a callback, it does the following:

- Output the comment for the method.
- Output the file and line of the declaration
- Analyse the parameters

|

.. figure:: ../../Images/Scalar/callback_analysis.png
	:width: 620px
	:alt: Callback analysis with comments and parameters

	Callback analysis with comments and parameters.