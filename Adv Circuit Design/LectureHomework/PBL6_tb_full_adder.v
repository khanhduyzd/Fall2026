
`timescale 1ns/1ps

module tb_full_adder;

    reg  [3:0] a, b;
    wire [4:0] c;

    integer i, j;
    integer errors;

    full_adder uut (
        .a(a),
        .b(b),
        .c(c)
    );

    initial begin
        a = 0;
        b = 0;
        errors = 0;

        for (i = 0; i < 16; i = i + 1) begin
            for (j = 0; j < 16; j = j + 1) begin
                a = i;
                b = j;
                #10;

                if (c !== (i + j)) begin
                    $display("ERROR: a=%d b=%d c=%d",
                             a, b, c);
                    errors = errors + 1;
                end
            end
        end

        if (errors == 0)
            $display("PASS: All 256 combinations passed.");
        else
            $display("FAIL: %0d errors.", errors);

        $finish;
    end

endmodule
