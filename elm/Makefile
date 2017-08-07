TARGET = ../curry-search/public/js

all: elm

elm:
	elm-make cs-autocomplete.elm --output tmp.js
	closure-compiler --js tmp.js --js_output_file $(TARGET)/cs-autocomplete.min.js
