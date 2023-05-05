.. include:: ../../Includes.txt


.. _administration-array-path:

Array Path configuration
^^^^^^^^^^^^^^^^^^^^^^^^


.. _administration-array-path-introduction:

Introduction
""""""""""""

The "arrayPath" property, which can apply to both the general configuration and the
columns configuration has several options which can make it tricky to use once
you try more complicated scenarios. Thus this dedicated chapter.

This property is like a path pointing some specific part of a multidimensional array.
The different parts of the path are separated by some marker, itself defined by the
:ref:`arrayPathSeparator <administration-general-tca-properties-arraypathseparator>` property.
if "arrayPathSeparator" is not set, the separator defaults to :code:`/`.


.. _administration-array-path-introduction-examples:

Examples
~~~~~~~~

As a simple example, consider the following structure to import:

.. code:: php

   [
      'name' => 'Zaphod Beeblebrox',
      'book' => [
         'title' => 'Hitchiker\'s Guide to the Galaxy'
      ]
   ]

To import the title of the book (and not the book itself), use the following configuration:

.. code:: php

   [
      'arrayPath' => 'book/title'
   ]

.. note::

   At column-level, using :code:`'arrayPath' => 'book'` is equivalent to using :code:`'field' => 'book'`,
   but the "field" property should be preferred in such a case, as it requires less processing.

If, for some reason, you needed a different separator, you could use something like:

.. code:: php

   [
      'arrayPath' => 'book#title',
      'arrayPathSeparator' => '#'
   ]

It is perfectly okay to use numerical indices in the path. With this structure:

.. code:: php

   [
      'series' => 'Hitchiker\'s Guide to the Galaxy',
      'books' => [
         'The Hitchiker\'s Guide to the Galaxy',
         'The Restaurant at the End of the Universe',
         'So long, and thanks for all the Fish'
         // etc.
      ]
   ]

and this configuration:

.. code:: php

   [
      'arrayPath' => 'books/0'
   ]

The result will be "The Hitchiker's Guide to the Galaxy". It is always the
first element inside "books" that will be selected.


.. _administration-array-path-conditions:

Conditions
""""""""""

Conditions can be applied to each segment of the path using the Symfony Expression Language syntax,
wrapped in curly braces. If the value being tested is an array, its items can be accessed directly
in the expression. If the value is a simple type, it can be accessed in the expression with the key :code:`value`.

See the `Symfony documentation for reference on the Symfony Expression Language syntax <https://symfony.com/doc/current/components/expression_language/syntax.html>`_.


.. _administration-array-path-conditions-examples:

Examples
~~~~~~~~

With the following data to import:

.. code:: php

   [
      'name' => 'Zaphod Beeblebrox',
      'book' => [
         'state' => 'new',
         'title' => 'Hitchiker\'s Guide to the Galaxy'
      ]
   ]

let's imagine two scenarios. First, we want to get the name of the character, but
only if it's "Zaphod Beeblebrox". The configuration would be:

.. code:: php

   [
      'arrayPath' => 'name{value === \'Zaphod Beeblebrox\'}'
   ]

When the name is indeed "Zaphod Beeblebrox", the result will be "Zaphod Beeblebrox" too.
When the name is anything else, the result will be :code:`null`.

A second scenario is to take the title of the book, only if the book is new. That would
be achieved with a configuration like:

.. code:: php

   [
      'arrayPath' => 'book{state === \'new\'}/title'
   ]

With the above data, the result will be "Hitchiker's Guide to the Galaxy", but for a book
whose state is "used", the result would be :code:`null`.

Such usage of conditions may seem a bit far-fetched at first, but can quite interesting
when combined (at a later stage in the import process) with the
:ref:`isEmpty property <administration-transformations-properties-isempty>`. However
conditions are much more interesting for looping on substructures and filtering them,
as described next.


.. _administration-array-path-looping-filtering:

Looping and filtering
"""""""""""""""""""""

The special segment :code:`*` can be included in the path. It indicates that all values
selected up to that point should be looped on and the condition following the :code:`*`
applied to each of them (using :code:`*` without a condition is meaningless). This will
effectively filter the currently selected elements. Further segments in the path are
applied only to that resulting set.

.. note::

   Using :code:`*` as a segment will always result in an array, which can be explored
   with further segments or :ref:`flattened <administration-general-tca-properties-arraypathflatten>`,
   if it contains a single result.

Usage of special segment :code:`*` can be followed by usage of special segment :code:`.`,
which changes the way the selected elements are handled. This is better explained
by using examples.


.. _administration-array-path-looping-filtering-examples:

Examples
~~~~~~~~

Let's consider the following structure to import:

.. code:: php

   [
       'test' => [
           'data' => [
               0 => [
                   'status' => 'valid',
                   'list' => [
                       0 => 'me',
                       1 => 'you'
                   ]
               ],
               1 => [
                   'status' => 'invalid',
                   'list' => [
                       4 => 'we'
                   ]
               ],
               2 => [
                   'status' => 'valid',
                   'list' => [
                       3 => 'them'
                   ]
               ]
           ]
       ]
   ]

And let's say that we want to have all the items that are inside the "list" key,
but only when the "status" is "valid". We would use the following configuration:

.. code:: php

   [
      'arrayPath' => 'test/data/*{status === \'valid\'}/list'
   ]

which would result in:

.. code:: php

   [
       0 => 'me',
       1 => 'you',
       2 => 'them'
   ]

This may not seem very intuitive at first. This is because this feature was designed
to mimic what you might get from a XML structure with a XPath query. Consider the
following structure:

.. code-block:: xml

   <books>
      <book>
         <title>Foo</title>
         <authors>
            <author>A</author>
            <author>B</author>
         </authors>
      </book>
      <book>
         <title>Bar</title>
         <authors>
            <author>C</author>
         </authors>
      </book>
   </books>

With an XPath like :code:`//author`, you would get values "A", "B" and "C" in a single
list, no matter what context surrounds them.

If you need to preserve the structure of the elements matched, you can add the special
segment :code:`.` after the :code:`*` segment. This preserves the matched structure,
to which you can apply further path segments. The above example would be modified
as such:

.. code:: php

   [
      'arrayPath' => 'test/data/*{status === \'valid\'}/./list'
   ]

which changes the result to:

.. code:: php

   [
       0 => [
           0 => 'me',
           1 => 'you'
       ],
       1 => [
           3 => 'them'
       ]
   ]

If we change the structure to import to this:

.. code:: php

   [
       'test' => [
           'data' => [
               0 => [
                   'status' => 'invalid',
                   'list' => [
                       0 => 'me',
                       1 => 'you'
                   ]
               ],
               1 => [
                   'status' => 'invalid',
                   'list' => [
                       4 => 'we'
                   ]
               ],
               2 => [
                   'status' => 'valid',
                   'list' => [
                       3 => 'them'
                   ]
               ]
           ]
       ]
   ]

making the first entry also "invalid" and using the same first condition:

.. code:: php

   [
      'arrayPath' => 'test/data/*{status === \'valid\'}/list'
   ]

we will have a single result:

.. code:: php

   [
       0 => 'them'
   ]

When we know that we have such a scenario, it might be convenient to get the actual
value as a result (i.e. "them") rather than a single-entry array. This is where
:ref:`property arrayPathFlatten <administration-general-tca-properties-arraypathflatten>`
can be used. Modifying the configuration to:

.. code:: php

   [
      'arrayPath' => 'test/data/*{status === \'valid\'}/list',
      'arrayPathFlatten' => true
   ]

changes the result to simply:

.. code:: php

   'them'
