# Intro

The Moodle Course Roadmap is a tool that empowers instructors to create an interactive, 
visual representation of the course structure and process. Think of it as a tracking 
guide for your course, helping students stay on top of their activity completion and 
progress. (Watch a [video overview](https://youtu.be/JvZIMqoDlzg?si=UyvlzamXcVxhj6fR) 
about Moodle Course Roadmap, duration 3:34).

## Installation

There are two installation methods that are available.

Follow one of these, then log into your Moodle site as an administrator and visit the 
notifications page to complete the install.

### Git

This requires Git being installed. If you do not have Git installed, please visit the 
[Git website](https://git-scm.com/downloads "Git website").

Once you have Git installed, simply visit your Moodle mod directory and clone the 
repository using the following command.

```
git clone git@github.com:ncstate-delta/moodle-mod_roadmap.git roadmap
```

Then checkout the branch corresponding to the version of Moodle you are using with 
the following command.

Use `git pull` to update this repository periodically to ensure you have the most 
recent updates.

### Download the zip

Visit the [Moodle plugins website](https://moodle.org/plugins/mod_roadmap 
"Moodle plugins website") and download the zip corresponding to the version of 
Moodle you are using. Extract the zip and place the 'roadmap' folder in the mod 
folder in your Moodle directory.