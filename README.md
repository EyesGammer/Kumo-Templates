# Kumo Templates parser
This is a little PHP lib that parse string templates, to generate HTML code.

## Files
### File: configuration.json
This file contain every tags that can be used to create a template.<br>
Each tag can use attributes. Attributes are also defined into this file.<br>
Each tag, and each attribute can also have default values.

**1. Create tag**<br>
Edit the file "configuration.json", add an entry onto "tags", inside the base object.<br>
Entry name will be the final tag, for example "ktest" will be translated "\[ktest]\[/ktest]" in a template string.<br>
You should define which HTML tag will be created by this tag, attributes that could be used on it, and default tags if needed.<br>
By using attributes already in the configuration JSON file, here is an example _(the attribute "new-bg" is the next example !)_ :
```
{
  "tags": {
    "ktest": {
      "tag": "span",
      "accept": [
        "new-bg",
        "size",
        "id",
        "class"
      ],
      "default": [
        "new-bg"
      ]
    }
  }
}
```
**2. Create attribute**<br>
Edit the file "configuration.json", add an entry onto "attributes", inside the base object.<br>
Entry name will be the final attribute to use, for example "new-bg" can be used in a tag object.<br>
By expanding the previous example, here is an example on how to use attributes :<br>
Let's say, we want a background attribute that can set a background color, a text color, and also a title. Here is how to do it :
```
{
  "tags": {
    "ktest": {
      "tag": "span",
      "accept": [
        "new-bg",
        "size",
        "id",
        "class"
      ],
      "default": [
        "new-bg"
      ]
    }
  },
  "attributes": {
    "new-bg": {
      "style": [
        "background-color: %0",
        "color: %1"
      ],
      "attributes": [
        "title"
      ],
      "values": [
        "%2"
      ]

    }
  }
}
```
The above configuration could be used like that :<br>
```[ktest size="10px" new-bg="blue red Cool-Test"]This is a test[/ktest]```<br>
And that will be translated to :<br>
```<span style="font-size: 10px;background-color: blue;color: red;" title="Cool-Test">This is a test</span>```<br>
###### This is a bad example, because we cannot actually have space in the "title" attribute, if we configure the "new-bg" attribute like that.

### File: index.php (test example)
This file is the main usage example of this library.<br>
Put configuration.json in the same folder as "index.php", then run :<br>
```sudo php -S localhost:80```

The template used in "index.php" is :<br>
```[kstyle link-css="./style.css"][/kstyle][krow wh="100% 100%" flex flex-col][krow class="flexed" wh="100% 100%" bg="black" flex flex-col flex-center][ktitle]Bienvenue sur ce site de test ![/ktitle][/krow][krow class="flexed" flex flex-row flex-center wh="100% 100%"][krow class="card-container" flex flex-row flex-center wh="50% 100%"][kimg wh="auto 33%" bordered][/kimg][kpar padding="1rem"]This is a perfect test paragraph :)[/kpar][/krow][/krow][/krow]```<br>

The webpage should looks like that :<br>
![Screenshot 2024-11-05 at 17 51 57](https://github.com/user-attachments/assets/5da03d90-3257-483f-845d-a42b291e5471)

### File : style.css (used in the above example)
We can include CSS files with the "kstyle" tag. The previous example is simply calling this file.

## Usage
To properly use this library, you will need to import the **"ktemplates.php"** file.<br>
Next, you should fetch configuration _(from JSON file or from API)_, set global "tags" and "attributes" _(from the JSON file, look at "index.php" to see how to do that)_.<br>
Send the template string content to the tags sorting function _(needed to render tags in right order)_.<br>
Finally, render the template content to the script, like that :<br>
```echo kumo_generate_html( $content, $result );```

I used this template's core in a previous project. The idea was to render advertising on the big screen, monitored by a Raspberry PI. This little template engine did the tricks.

---
Todo list :
- Set "tags" and "attributes" by another way than global variables.
- Create nested tags, to directly create blocks _(example: "card" block, to feature an image and a text...)_.
- Add default values to attributes when having DOM attributes and CSS style properties.
