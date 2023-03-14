#open a file for read and write called dados.csv
import hashlib

file = open('dados.csv', 'r+')

for line in file:
    bytes_to_hash = line.replace(',', ' ').encode('utf-8')
    hash_object = hashlib.md5()
    hash_object.update(bytes_to_hash)
    hex_hash = hash_object.hexdigest()
    
    print(hex_hash)
