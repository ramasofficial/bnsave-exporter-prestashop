#!/bin/bash

file=releases/bnsave-exporter.zip
[ -f $file ] && rm $file
git archive HEAD -o $file
